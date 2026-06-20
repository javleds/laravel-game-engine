<?php

namespace TurnEngine\Laravel\Contracts;

use TurnEngine\Laravel\Models\EngineRoom;

interface GamePolicy
{
    public function minPlayers(): int;

    public function maxPlayers(): int;

    public function canJoin(EngineRoom $room): bool;

    public function canLeave(EngineRoom $room): bool;

    public function isChatOpen(EngineRoom $room): bool;
}
