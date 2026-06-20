<?php

namespace TurnEngine\Laravel\Rooms;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use TurnEngine\Laravel\GameRegistry;
use TurnEngine\Laravel\Models\EnginePlayer;
use TurnEngine\Laravel\Models\EngineRoom;

final class CreateRoomService
{
    public function __construct(private readonly GameRegistry $games) {}

    /**
     * @return array<string, string>
     */
    public function create(string $nickname): array
    {
        $roomId = $this->roomId();
        $playerId = (string) Str::uuid();
        $token = Str::random(48);

        $game = $this->games->default();

        DB::transaction(function () use ($roomId, $playerId, $nickname, $token, $game): void {
            EngineRoom::query()->create([
                'id' => $roomId,
                'status' => RoomStatus::Lobby->value,
                'host_player_id' => $playerId,
                'ruleset_version' => $game->rulesetVersion(),
            ]);

            EnginePlayer::query()->create([
                'id' => $playerId,
                'room_id' => $roomId,
                'nickname' => $nickname,
                'token_hash' => Hash::make($token),
                'is_host' => true,
                'connected' => true,
                'last_seen_at' => now(),
            ]);
        });

        return [
            'roomId' => $roomId,
            'roomStatus' => RoomStatus::Lobby->value,
            'playerId' => $playerId,
            'playerToken' => $token,
            'publicInviteUrl' => url("/join/{$roomId}"),
            'privateReconnectUrl' => url("/rooms/{$roomId}/resume?player={$playerId}&token={$token}"),
        ];
    }

    private function roomId(): string
    {
        do {
            $roomId = Str::upper(Str::random(6));
        } while (EngineRoom::query()->whereKey($roomId)->exists());

        return $roomId;
    }
}
