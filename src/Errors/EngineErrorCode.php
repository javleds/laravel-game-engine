<?php

namespace TurnEngine\Laravel\Errors;

enum EngineErrorCode: string
{
    case StaleState = 'STALE_STATE';
}
