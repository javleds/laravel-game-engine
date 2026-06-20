<?php

namespace TurnEngine\Laravel\Contracts;

interface GameSetupHandler
{
    /**
     * @param  array<int, array{playerId: string, nickname: string}>  $players
     * @return array<string, mixed>
     */
    public function start(string $roomId, array $players, string $seed): array;
}
