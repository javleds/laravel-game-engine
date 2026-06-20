<?php

namespace TurnEngine\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EngineGameEvent extends Model
{
    protected $table = 'game_events';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'room_id',
        'player_id',
        'command_id',
        'event_type',
        'event_json',
        'state_version',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'event_json' => 'array',
            'state_version' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(EngineRoom::class, 'room_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(EnginePlayer::class, 'player_id');
    }
}
