<?php

namespace TurnEngine\Laravel\Realtime;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

final class RoomStateChanged implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public readonly string $roomId,
        public readonly int $stateVersion
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("room.{$this->roomId}");
    }

    public function broadcastAs(): string
    {
        return 'room.state.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'roomId' => $this->roomId,
            'stateVersion' => $this->stateVersion,
        ];
    }
}
