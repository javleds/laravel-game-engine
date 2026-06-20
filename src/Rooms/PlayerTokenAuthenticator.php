<?php

namespace TurnEngine\Laravel\Rooms;

use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use TurnEngine\Laravel\Models\EnginePlayer;

final class PlayerTokenAuthenticator
{
    public function authenticate(string $roomId, ?string $bearerToken): EnginePlayer
    {
        if (! $bearerToken) {
            throw new AccessDeniedHttpException('Missing player token.');
        }

        $players = EnginePlayer::query()->where('room_id', $roomId)->get();

        foreach ($players as $player) {
            if (Hash::check($bearerToken, $player->token_hash)) {
                return $player;
            }
        }

        throw new AccessDeniedHttpException('Invalid player token.');
    }
}
