<?php

namespace TurnEngine\Laravel\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TurnEngine\Laravel\Chat\ChatService;
use TurnEngine\Laravel\Contracts\GameViewFactory;
use TurnEngine\Laravel\Errors\EngineActionException;
use TurnEngine\Laravel\Errors\EngineErrorCode;
use TurnEngine\Laravel\Models\EngineChatMessage;
use TurnEngine\Laravel\Models\EngineGameEvent;
use TurnEngine\Laravel\Models\EngineGameState;
use TurnEngine\Laravel\Realtime\RoomChatMessageCreated;
use TurnEngine\Laravel\Realtime\RoomStateChanged;
use TurnEngine\Laravel\Rooms\PlayerTokenAuthenticator;

final class CommandRunner
{
    public function __construct(private readonly PlayerTokenAuthenticator $authenticator) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function run(
        string $roomId,
        ?string $token,
        array $payload,
        CommandHandler $handler,
        GameViewFactory $viewFactory
    ): array {
        $player = $this->authenticator->authenticate($roomId, $token);

        return DB::transaction(function () use ($roomId, $player, $payload, $handler, $viewFactory): array {
            $commandId = $payload['commandId'];
            $existingEvent = EngineGameEvent::query()
                ->where('room_id', $roomId)
                ->where('command_id', $commandId)
                ->first();

            /** @var EngineGameState|null $gameState */
            $gameState = EngineGameState::query()->lockForUpdate()->where('room_id', $roomId)->first();

            if (! $gameState) {
                throw new HttpException(404, 'Game state not found.');
            }

            if ($existingEvent) {
                return $viewFactory->make($gameState->state_json, $player->id);
            }

            if ($gameState->state_version !== $payload['expectedStateVersion']) {
                throw new EngineActionException(EngineErrorCode::StaleState);
            }

            $result = $handler->handle($gameState->state_json, $player->id, $payload, $player->nickname);
            $state = [
                ...$result->state,
                'stateVersion' => $gameState->state_version + 1,
            ];

            if ($result->purgeChatMessages) {
                EngineChatMessage::query()->where('room_id', $roomId)->delete();
            }

            $gameState->update([
                'state_version' => $state['stateVersion'],
                'state_json' => $state,
                'updated_at' => now(),
            ]);

            EngineGameEvent::query()->create([
                'id' => (string) Str::uuid(),
                'room_id' => $roomId,
                'player_id' => $player->id,
                'command_id' => $commandId,
                'event_type' => $result->eventType,
                'event_json' => [
                    'type' => $result->eventType,
                    'playerId' => $player->id,
                    'stateVersion' => $state['stateVersion'],
                ],
                'state_version' => $state['stateVersion'],
                'created_at' => now(),
            ]);

            if ($result->activityMessage !== null && $result->broadcastActivityMessage) {
                $chatMessage = EngineChatMessage::query()->create([
                    'id' => (string) Str::uuid(),
                    'room_id' => $roomId,
                    'player_id' => $player->id,
                    'nickname_snapshot' => 'Sistema',
                    'kind' => ChatService::KIND_ACTIVITY,
                    'message' => $result->activityMessage,
                    'created_at' => now(),
                ]);

                RoomChatMessageCreated::dispatch($roomId, $chatMessage->id);
            }

            RoomStateChanged::dispatch($roomId, $state['stateVersion']);

            return $viewFactory->make($state, $player->id);
        });
    }
}
