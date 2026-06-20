<?php

namespace TurnEngine\Laravel\Rooms;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TurnEngine\Laravel\GameRegistry;
use TurnEngine\Laravel\Models\EngineGameState;
use TurnEngine\Laravel\Models\EnginePlayer;
use TurnEngine\Laravel\Models\EngineRoom;
use TurnEngine\Laravel\Realtime\RoomStateChanged;

final class StartGameService
{
    public function __construct(
        private readonly PlayerTokenAuthenticator $authenticator,
        private readonly GameRegistry $games
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function start(string $roomId, ?string $bearerToken): array
    {
        $player = $this->authenticator->authenticate($roomId, $bearerToken);

        return DB::transaction(function () use ($roomId, $player): array {
            /** @var EngineRoom|null $room */
            $room = EngineRoom::query()->lockForUpdate()->find($roomId);

            if (! $room) {
                throw new HttpException(404, 'Room not found.');
            }

            if ($room->status !== RoomStatus::Lobby->value) {
                throw new HttpException(409, 'Room is not in lobby.');
            }

            if ($room->host_player_id !== $player->id) {
                throw new HttpException(403, 'Only the host can start the game.');
            }

            $game = $this->games->default();
            $policy = $game->policy();
            $connectedPlayerCount = EnginePlayer::query()
                ->where('room_id', $roomId)
                ->where('connected', true)
                ->count();
            $players = EnginePlayer::query()
                ->where('room_id', $roomId)
                ->orderBy('created_at')
                ->get();

            if ($connectedPlayerCount < $policy->minPlayers() || $players->count() > $policy->maxPlayers()) {
                throw new HttpException(
                    422,
                    sprintf(
                        'A game requires at least %d connected players and at most %d total players.',
                        $policy->minPlayers(),
                        $policy->maxPlayers()
                    )
                );
            }

            $state = $game->setupHandler()->start(
                $roomId,
                $players->map(fn (EnginePlayer $player): array => [
                    'playerId' => $player->id,
                    'nickname' => $player->nickname,
                ])->all(),
                $roomId
            );

            foreach ($state['players'] as $statePlayer) {
                EnginePlayer::query()
                    ->whereKey($statePlayer['playerId'])
                    ->update(['turn_order_index' => $statePlayer['turnOrderIndex']]);
            }

            $room->update([
                'status' => RoomStatus::Active->value,
                'started_at' => now(),
            ]);

            EngineGameState::query()->updateOrCreate(
                ['room_id' => $roomId],
                [
                    'state_version' => $state['stateVersion'],
                    'state_json' => $state,
                    'updated_at' => now(),
                ]
            );

            RoomStateChanged::dispatch($roomId, $state['stateVersion']);

            return $game->viewFactory()->make($state, $player->id);
        });
    }
}
