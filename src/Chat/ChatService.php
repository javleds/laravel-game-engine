<?php

namespace TurnEngine\Laravel\Chat;

use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TurnEngine\Laravel\GameRegistry;
use TurnEngine\Laravel\Models\EngineChatMessage;
use TurnEngine\Laravel\Models\EngineRoom;
use TurnEngine\Laravel\Realtime\RoomChatMessageCreated;
use TurnEngine\Laravel\Rooms\PlayerTokenAuthenticator;

final class ChatService
{
    public const KIND_ACTIVITY = 'activity';

    public const KIND_CHAT = 'chat';

    public function __construct(
        private readonly PlayerTokenAuthenticator $authenticator,
        private readonly GameRegistry $games
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function send(string $roomId, ?string $token, string $message): array
    {
        $player = $this->authenticator->authenticate($roomId, $token);
        $room = EngineRoom::query()->findOrFail($roomId);

        if (! $this->games->default()->policy()->isChatOpen($room)) {
            throw new HttpException(409, 'Chat is closed for finished games.');
        }

        $chatMessage = EngineChatMessage::query()->create([
            'id' => (string) Str::uuid(),
            'room_id' => $roomId,
            'player_id' => $player->id,
            'nickname_snapshot' => $player->nickname,
            'kind' => self::KIND_CHAT,
            'message' => $message,
            'created_at' => now(),
        ]);

        RoomChatMessageCreated::dispatch($roomId, $chatMessage->id);

        return $this->messageView($chatMessage);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(string $roomId, ?string $token, string $kind = self::KIND_CHAT): array
    {
        $this->authenticator->authenticate($roomId, $token);

        return EngineChatMessage::query()
            ->where('room_id', $roomId)
            ->where('kind', $kind)
            ->orderBy('created_at')
            ->limit(200)
            ->get()
            ->map(fn (EngineChatMessage $message): array => $this->messageView($message))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function messageView(EngineChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'roomId' => $message->room_id,
            'playerId' => $message->player_id,
            'nickname' => $message->nickname_snapshot,
            'kind' => $message->kind,
            'message' => $message->message,
            'createdAt' => $message->created_at?->toISOString(),
        ];
    }
}
