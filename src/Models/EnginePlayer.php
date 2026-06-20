<?php

namespace TurnEngine\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnginePlayer extends Model
{
    protected $table = 'players';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'room_id',
        'nickname',
        'token_hash',
        'is_host',
        'turn_order_index',
        'connected',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'is_host' => 'boolean',
            'connected' => 'boolean',
            'turn_order_index' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(EngineRoom::class, 'room_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(EngineGameEvent::class, 'player_id');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(EngineChatMessage::class, 'player_id');
    }
}
