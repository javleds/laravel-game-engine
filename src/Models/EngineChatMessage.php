<?php

namespace TurnEngine\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EngineChatMessage extends Model
{
    protected $table = 'chat_messages';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'room_id',
        'player_id',
        'nickname_snapshot',
        'kind',
        'message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
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
