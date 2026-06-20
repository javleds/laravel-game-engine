<?php

namespace TurnEngine\Laravel\Commands;

interface CommandHandler
{
    public function type(): string;

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $state, string $playerId, array $payload, string $nickname): CommandResult;
}
