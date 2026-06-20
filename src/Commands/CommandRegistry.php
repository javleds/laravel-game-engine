<?php

namespace TurnEngine\Laravel\Commands;

use InvalidArgumentException;

final class CommandRegistry
{
    /** @var array<string, CommandHandler> */
    private array $handlers = [];

    /**
     * @param  iterable<int, CommandHandler>  $handlers
     */
    public function __construct(iterable $handlers)
    {
        foreach ($handlers as $handler) {
            $this->handlers[$handler->type()] = $handler;
        }
    }

    public function get(string $type): CommandHandler
    {
        return $this->handlers[$type] ?? throw new InvalidArgumentException("Unknown command type: {$type}");
    }
}
