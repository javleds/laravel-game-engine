<?php

namespace TurnEngine\Laravel\Contracts;

use TurnEngine\Laravel\Commands\CommandRegistry;

interface GameDefinition
{
    public function key(): string;

    public function rulesetVersion(): string;

    public function policy(): GamePolicy;

    public function setupHandler(): GameSetupHandler;

    public function commandRegistry(): CommandRegistry;

    public function viewFactory(): GameViewFactory;
}
