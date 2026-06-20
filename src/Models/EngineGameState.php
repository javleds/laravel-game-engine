<?php

namespace TurnEngine\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EngineGameState extends Model
{
    protected $table = 'game_states';

    protected $primaryKey = 'room_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'room_id',
        'state_version',
        'state_json',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'state_version' => 'integer',
            'state_json' => 'array',
            'updated_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(EngineRoom::class, 'room_id');
    }
}
