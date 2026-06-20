<?php

namespace TurnEngine\Laravel\Errors;

use RuntimeException;

final class EngineActionException extends RuntimeException
{
    public function __construct(public readonly EngineErrorCode $codeEnum)
    {
        parent::__construct($codeEnum->value);
    }
}
