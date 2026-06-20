<?php

namespace TurnEngine\Laravel\Rooms;

use Symfony\Component\HttpKernel\Exception\HttpException;
use TurnEngine\Laravel\Models\EngineRoom;
use TurnEngine\Laravel\Realtime\RoomStateChanged;

final class DisconnectRoomService
{
    public function __construct(private readonly PlayerTokenAuthenticator $authenticator) {}

    /**
     * @return array{roomId: string, playerId: string, connected: bool}
     */
    public function disconnect(string $roomId, ?string $bearerToken): array
    {
        $player = $this->authenticator->authenticate($roomId, $bearerToken);

        if (! EngineRoom::query()->whereKey($roomId)->exists()) {
            throw new HttpException(404, 'Room not found.');
        }

        if ($player->connected) {
            $player->forceFill([
                'connected' => false,
                'last_seen_at' => now(),
            ])->save();

            RoomStateChanged::dispatch($roomId, 0);
        }

        return [
            'roomId' => $roomId,
            'playerId' => $player->id,
            'connected' => false,
        ];
    }
}
