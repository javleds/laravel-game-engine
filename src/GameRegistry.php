<?php

namespace TurnEngine\Laravel;

use InvalidArgumentException;
use TurnEngine\Laravel\Contracts\GameDefinition;

final class GameRegistry
{
    /** @var array<string, GameDefinition> */
    private array $definitions = [];

    /**
     * @param  iterable<int, GameDefinition>  $definitions
     */
    public function __construct(iterable $definitions = [])
    {
        foreach ($definitions as $definition) {
            $this->register($definition);
        }
    }

    public function register(GameDefinition $definition): void
    {
        $this->definitions[$definition->key()] = $definition;
    }

    public function default(): GameDefinition
    {
        $defaultGame = config('turn-engine.default_game');

        if (is_string($defaultGame) && $defaultGame !== '') {
            return $this->get($defaultGame);
        }

        if (count($this->definitions) === 1) {
            return reset($this->definitions);
        }

        throw new InvalidArgumentException('No default game configured.');
    }

    public function get(string $key): GameDefinition
    {
        return $this->definitions[$key] ?? throw new InvalidArgumentException("Unknown game: {$key}");
    }
}
