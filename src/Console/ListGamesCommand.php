<?php

namespace TurnEngine\Laravel\Console;

use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use TurnEngine\Laravel\Models\EnginePlayer;
use TurnEngine\Laravel\Models\EngineRoom;
use TurnEngine\Laravel\Rooms\RoomStatus;

final class ListGamesCommand extends Command
{
    protected $signature = 'app:games:list
        {--compact : Hide the player details table}
        {--all-stages : Include every game stage. This is the default behavior}
        {--active-only : Include only games currently being played}';

    protected $description = 'List game rooms with operational details.';

    public function handle(): int
    {
        $query = EngineRoom::query()
            ->with([
                'players' => fn ($query) => $query->orderBy('turn_order_index')->orderBy('created_at'),
                'gameState',
            ])
            ->withCount(['events', 'chatMessages']);

        if ($this->option('active-only')) {
            $query->whereIn('status', [
                RoomStatus::Active->value,
                RoomStatus::FinalRound->value,
            ]);
        }

        $rooms = $query
            ->get()
            ->sortByDesc(fn (EngineRoom $room): int => $this->lastUpdatedAt($room)?->getTimestamp() ?? 0)
            ->values();

        if ($rooms->isEmpty()) {
            $this->info('No games found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Room', 'Stage', 'Started', 'Last update', 'Round', 'Version', 'Turn', 'Host', 'Players', 'Online', 'Events', 'Chat'],
            $rooms->map(fn (EngineRoom $room): array => $this->row($room))->all()
        );

        if (! $this->option('compact')) {
            $this->line('');
            $this->table(
                ['RoomID', 'Player', 'Host', 'Status'],
                $this->playerRows($rooms)
            );
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, string|int>
     */
    private function row(EngineRoom $room): array
    {
        $state = $room->gameState?->state_json ?? [];
        $players = $room->players;
        $connectedPlayers = $players->filter(fn (EnginePlayer $player): bool => $player->connected);

        return [
            $room->id,
            $this->stage($room),
            $this->date($room->started_at),
            $this->date($this->lastUpdatedAt($room)),
            (int) ($state['roundNumber'] ?? 0),
            $room->gameState?->state_version ?? 0,
            $this->currentTurn($players, $state['currentPlayerId'] ?? null),
            $this->host($players),
            $players->count(),
            "{$connectedPlayers->count()}/{$players->count()}",
            $room->events_count,
            $room->chat_messages_count,
        ];
    }

    private function stage(EngineRoom $room): string
    {
        return match ($room->status) {
            RoomStatus::Lobby->value => 'Lobby',
            RoomStatus::Active->value => 'Game',
            RoomStatus::FinalRound->value => 'Final round',
            RoomStatus::Finished->value => 'Results',
            RoomStatus::Abandoned->value => 'Abandoned',
            default => $room->status,
        };
    }

    private function lastUpdatedAt(EngineRoom $room): ?CarbonInterface
    {
        $roomUpdatedAt = $room->updated_at;
        $stateUpdatedAt = $room->gameState?->updated_at;

        if (! $roomUpdatedAt) {
            return $stateUpdatedAt;
        }

        if (! $stateUpdatedAt) {
            return $roomUpdatedAt;
        }

        return $stateUpdatedAt->greaterThan($roomUpdatedAt) ? $stateUpdatedAt : $roomUpdatedAt;
    }

    private function date(?CarbonInterface $date): string
    {
        return $date?->format('Y-m-d H:i:s') ?? '-';
    }

    /**
     * @param  Collection<int, EnginePlayer>  $players
     */
    private function currentTurn(Collection $players, mixed $currentPlayerId): string
    {
        if (! is_string($currentPlayerId) || $currentPlayerId === '') {
            return '-';
        }

        $player = $players->firstWhere('id', $currentPlayerId);

        if (! $player) {
            return $currentPlayerId;
        }

        return $player->nickname;
    }

    /**
     * @param  Collection<int, EnginePlayer>  $players
     */
    private function host(Collection $players): string
    {
        $host = $players->first(fn (EnginePlayer $player): bool => $player->is_host);

        return $host?->nickname ?? '-';
    }

    /**
     * @param  Collection<int, EngineRoom>  $rooms
     * @return array<int, array<int, string>>
     */
    private function playerRows(Collection $rooms): array
    {
        $rows = [];

        foreach ($rooms as $room) {
            foreach ($this->orderedPlayers($room->players) as $player) {
                $rows[] = [
                    $room->id,
                    $player->nickname,
                    $player->is_host ? '*' : '',
                    $player->connected ? 'online' : 'offline',
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  Collection<int, EnginePlayer>  $players
     * @return Collection<int, EnginePlayer>
     */
    private function orderedPlayers(Collection $players): Collection
    {
        return $players
            ->sortBy(fn (EnginePlayer $player): string => sprintf(
                '%d-%03d-%010d',
                $player->is_host ? 0 : 1,
                $player->turn_order_index ?? 255,
                $player->created_at?->getTimestamp() ?? 0
            ))
            ->values();
    }
}
