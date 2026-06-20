<?php

namespace TurnEngine\Laravel\Contracts;

interface GameViewFactory
{
    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public function make(array $state, string $viewerPlayerId): array;
}
