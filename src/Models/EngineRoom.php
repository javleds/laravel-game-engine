<?php

namespace TurnEngine\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EngineRoom extends Model
{
    protected $table = 'rooms';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'status',
        'host_player_id',
        'ruleset_version',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function players(): HasMany
    {
        return $this->hasMany(EnginePlayer::class, 'room_id');
    }

    public function gameState(): HasOne
    {
        return $this->hasOne(EngineGameState::class, 'room_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(EngineGameEvent::class, 'room_id');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(EngineChatMessage::class, 'room_id');
    }
}
