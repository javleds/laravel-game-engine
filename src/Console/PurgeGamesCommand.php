<?php

namespace TurnEngine\Laravel\Console;

use Illuminate\Console\Command;
use TurnEngine\Laravel\Maintenance\GamePurgeResult;
use TurnEngine\Laravel\Maintenance\GamePurgeService;

final class PurgeGamesCommand extends Command
{
    protected $signature = 'app:batch:games:delete
        {--older-than= : Delete rooms whose last room or game update is older than or equal to this number of days}
        {--all : Delete every room}';

    protected $description = 'Delete game rooms by empty player list, finished status, inactivity, or all rooms.';

    public function handle(GamePurgeService $purgeService): int
    {
        if ($this->option('all')) {
            $this->report($purgeService->purgeAll());

            return self::SUCCESS;
        }

        $olderThanDays = $this->olderThanDaysOption();

        if ($olderThanDays === null) {
            $this->report($purgeService->purgeDefaultRooms());

            return self::SUCCESS;
        }

        if ($olderThanDays < 1) {
            $this->error('The --older-than option must be a positive integer.');

            return self::FAILURE;
        }

        $this->report($purgeService->purgeDefaultAndInactiveRooms($olderThanDays));

        return self::SUCCESS;
    }

    private function olderThanDaysOption(): ?int
    {
        $value = $this->option('older-than');

        if ($value === null) {
            return null;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT);

        if ($integer === false) {
            return 0;
        }

        return $integer;
    }

    private function report(GamePurgeResult $result): void
    {
        $this->info("Deleted {$result->deletedRooms} room(s).");
    }
}
