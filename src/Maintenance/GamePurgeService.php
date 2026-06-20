<?php

namespace TurnEngine\Laravel\Maintenance;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use TurnEngine\Laravel\Models\EngineRoom;
use TurnEngine\Laravel\Rooms\RoomStatus;

final class GamePurgeService
{
    public function purgeRoom(string $roomId): GamePurgeResult
    {
        return $this->purgeRoomIds([$roomId]);
    }

    public function purgeAll(): GamePurgeResult
    {
        /** @var list<string> $roomIds */
        $roomIds = EngineRoom::query()
            ->orderBy('id')
            ->pluck('id')
            ->all();

        return $this->purgeRoomIds($roomIds);
    }

    public function purgeEmptyRooms(): GamePurgeResult
    {
        return $this->purgeRoomIds($this->emptyRoomIds());
    }

    public function purgeDefaultRooms(): GamePurgeResult
    {
        return $this->purgeRoomIds($this->defaultRoomIds());
    }

    public function purgeDefaultAndInactiveRooms(int $inactiveDays): GamePurgeResult
    {
        return $this->purgeRoomIds([
            ...$this->defaultRoomIds(),
            ...$this->inactiveRoomIds($inactiveDays),
        ]);
    }

    public function purgeInactiveRooms(int $inactiveDays): GamePurgeResult
    {
        return $this->purgeRoomIds($this->inactiveRoomIds($inactiveDays));
    }

    /**
     * @param  list<string>  $roomIds
     */
    public function purgeRoomIds(array $roomIds): GamePurgeResult
    {
        $uniqueRoomIds = Collection::make($roomIds)
            ->filter(fn (string $roomId): bool => $roomId !== '')
            ->unique()
            ->values()
            ->all();

        if ($uniqueRoomIds === []) {
            return new GamePurgeResult(0, []);
        }

        $deletedRooms = DB::transaction(function () use ($uniqueRoomIds): int {
            return EngineRoom::query()
                ->whereIn('id', $uniqueRoomIds)
                ->delete();
        });

        return new GamePurgeResult($deletedRooms, $uniqueRoomIds);
    }

    /**
     * @return list<string>
     */
    private function emptyRoomIds(): array
    {
        /** @var list<string> $roomIds */
        $roomIds = EngineRoom::query()
            ->whereDoesntHave('players')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        return $roomIds;
    }

    /**
     * @return list<string>
     */
    private function defaultRoomIds(): array
    {
        return [
            ...$this->emptyRoomIds(),
            ...$this->finishedRoomIds(),
        ];
    }

    /**
     * @return list<string>
     */
    private function finishedRoomIds(): array
    {
        /** @var list<string> $roomIds */
        $roomIds = EngineRoom::query()
            ->where('status', RoomStatus::Finished->value)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        return $roomIds;
    }

    /**
     * @return list<string>
     */
    private function inactiveRoomIds(int $inactiveDays): array
    {
        $cutoff = CarbonImmutable::now()->subDays($inactiveDays);

        /** @var list<string> $roomIds */
        $roomIds = EngineRoom::query()
            ->leftJoin('game_states', 'rooms.id', '=', 'game_states.room_id')
            ->where(function ($query) use ($cutoff): void {
                $query
                    ->where(function ($query) use ($cutoff): void {
                        $query
                            ->whereNotNull('game_states.updated_at')
                            ->where('game_states.updated_at', '<=', $cutoff);
                    })
                    ->orWhere(function ($query) use ($cutoff): void {
                        $query
                            ->whereNull('game_states.updated_at')
                            ->where('rooms.updated_at', '<=', $cutoff);
                    });
            })
            ->orderBy('rooms.id')
            ->pluck('rooms.id')
            ->all();

        return $roomIds;
    }
}
