<?php

namespace TurnEngine\Laravel\Maintenance;

final readonly class GamePurgeResult
{
    /**
     * @param  list<string>  $roomIds
     */
    public function __construct(
        public int $deletedRooms,
        public array $roomIds
    ) {}
}
