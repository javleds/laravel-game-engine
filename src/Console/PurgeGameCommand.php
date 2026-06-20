<?php

namespace TurnEngine\Laravel\Console;

use Illuminate\Console\Command;
use TurnEngine\Laravel\Maintenance\GamePurgeService;

final class PurgeGameCommand extends Command
{
    protected $signature = 'game-engine:games:delete {roomId : Room id to delete}';

    protected $description = 'Delete one game room by room id.';

    public function handle(GamePurgeService $purgeService): int
    {
        $roomId = (string) $this->argument('roomId');
        $result = $purgeService->purgeRoom($roomId);

        if ($result->deletedRooms === 0) {
            $this->warn("Room {$roomId} was not found.");

            return self::FAILURE;
        }

        $this->info("Deleted room {$roomId}.");

        return self::SUCCESS;
    }
}
