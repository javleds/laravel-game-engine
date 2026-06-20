<?php

namespace TurnEngine\Laravel\Rooms;

use Symfony\Component\HttpKernel\Exception\HttpException;
use TurnEngine\Laravel\GameRegistry;
use TurnEngine\Laravel\Models\EngineRoom;
use TurnEngine\Laravel\Realtime\RoomStateChanged;

final class LeaveRoomService
{
    public function __construct(
        private readonly PlayerTokenAuthenticator $authenticator,
        private readonly GameRegistry $games
    ) {}

    /**
     * @return array{roomId: string, playerId: string, left: bool}
     */
    public function leave(string $roomId, ?string $bearerToken): array
    {
        $player = $this->authenticator->authenticate($roomId, $bearerToken);
        $room = EngineRoom::query()->find($roomId);

        if (! $room) {
            throw new HttpException(404, 'Room not found.');
        }

        if (! $this->games->default()->policy()->canLeave($room)) {
            throw new HttpException(409, 'Only lobby players can leave the room.');
        }

        $player->delete();

        RoomStateChanged::dispatch($roomId, 0);

        return [
            'roomId' => $roomId,
            'playerId' => $player->id,
            'left' => true,
        ];
    }
}
