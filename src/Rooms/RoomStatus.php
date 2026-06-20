<?php

namespace TurnEngine\Laravel\Rooms;

enum RoomStatus: string
{
    case Lobby = 'LOBBY';
    case Active = 'ACTIVE';
    case FinalRound = 'FINAL_ROUND';
    case Finished = 'FINISHED';
    case Abandoned = 'ABANDONED';
}
