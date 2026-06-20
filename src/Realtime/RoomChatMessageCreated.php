<?php

namespace TurnEngine\Laravel\Realtime;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

final class RoomChatMessageCreated implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public readonly string $roomId,
        public readonly string $messageId
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("room.{$this->roomId}");
    }

    public function broadcastAs(): string
    {
        return 'room.chat.message.created';
    }

    /**
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return [
            'roomId' => $this->roomId,
            'messageId' => $this->messageId,
        ];
    }
}
