<?php

namespace TurnEngine\Laravel\Commands;

final readonly class CommandResult
{
    /**
     * @param  array<string, mixed>  $state
     */
    public function __construct(
        public array $state,
        public string $eventType,
        public ?string $activityMessage = null,
        public bool $purgeChatMessages = false,
        public bool $broadcastActivityMessage = true,
    ) {}
}
