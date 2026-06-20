<?php

namespace TurnEngine\Laravel\Rooms;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TurnEngine\Laravel\GameRegistry;
use TurnEngine\Laravel\Models\EnginePlayer;
use TurnEngine\Laravel\Models\EngineRoom;
use TurnEngine\Laravel\Realtime\RoomStateChanged;

final class JoinRoomService
{
    public function __construct(private readonly GameRegistry $games) {}

    /**
     * @return array{roomId: string, roomStatus: string, playerId: string, playerToken: string, publicInviteUrl: string, privateReconnectUrl: string, recovered: bool}
     */
    public function join(string $roomId, string $nickname): array
    {
        $room = EngineRoom::query()->find($roomId);

        if (! $room) {
            throw new NotFoundHttpException('Room not found.');
        }

        $existingPlayer = $this->findPlayerByExactNickname($roomId, $nickname);

        if ($existingPlayer) {
            return $this->recoverPlayer($room, $existingPlayer);
        }

        if ($this->nicknameExistsWithDifferentCase($roomId, $nickname)) {
            throw new HttpException(409, 'Nickname case does not match an existing player in this room.');
        }

        $policy = $this->games->default()->policy();

        if (! $policy->canJoin($room)) {
            throw new HttpException(409, 'Room is not in lobby.');
        }

        $playerCount = EnginePlayer::query()
            ->where('room_id', $roomId)
            ->count();

        $maxPlayers = $policy->maxPlayers();

        if ($playerCount >= $maxPlayers) {
            throw new HttpException(422, "A room cannot have more than {$maxPlayers} players.");
        }

        $playerId = (string) Str::uuid();
        $token = Str::random(48);

        EnginePlayer::query()->create([
            'id' => $playerId,
            'room_id' => $roomId,
            'nickname' => $nickname,
            'token_hash' => Hash::make($token),
            'is_host' => false,
            'connected' => true,
            'last_seen_at' => now(),
        ]);

        RoomStateChanged::dispatch($roomId, 0);

        return [
            'roomId' => $roomId,
            'roomStatus' => $room->status,
            'playerId' => $playerId,
            'playerToken' => $token,
            'publicInviteUrl' => url("/join/{$roomId}"),
            'privateReconnectUrl' => url("/rooms/{$roomId}/resume?player={$playerId}&token={$token}"),
            'recovered' => false,
        ];
    }

    private function recoverPlayer(EngineRoom $room, EnginePlayer $player): array
    {
        if (! in_array($room->status, [
            RoomStatus::Lobby->value,
            RoomStatus::Active->value,
            RoomStatus::FinalRound->value,
        ], true)) {
            throw new HttpException(409, 'Room is not available for rejoin.');
        }

        $token = Str::random(48);

        $player->forceFill([
            'token_hash' => Hash::make($token),
            'connected' => true,
            'last_seen_at' => now(),
        ])->save();

        RoomStateChanged::dispatch($room->id, 0);

        return [
            'roomId' => $room->id,
            'roomStatus' => $room->status,
            'playerId' => $player->id,
            'playerToken' => $token,
            'publicInviteUrl' => url("/join/{$room->id}"),
            'privateReconnectUrl' => url("/rooms/{$room->id}/resume?player={$player->id}&token={$token}"),
            'recovered' => true,
        ];
    }

    private function findPlayerByExactNickname(string $roomId, string $nickname): ?EnginePlayer
    {
        /** @var Collection<int, EnginePlayer> $players */
        $players = EnginePlayer::query()
            ->where('room_id', $roomId)
            ->get();

        return $players->first(
            fn (EnginePlayer $player): bool => hash_equals($player->nickname, $nickname)
        );
    }

    private function nicknameExistsWithDifferentCase(string $roomId, string $nickname): bool
    {
        /** @var Collection<int, EnginePlayer> $players */
        $players = EnginePlayer::query()
            ->where('room_id', $roomId)
            ->get();

        return $players->contains(
            fn (EnginePlayer $player): bool => strtolower($player->nickname) === strtolower($nickname)
        );
    }
}
