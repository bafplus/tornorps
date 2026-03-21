<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarMember extends Model
{
    protected $fillable = [
        'war_id',
        'faction_id',
        'player_id',
        'name',
        'level',
        'rank',
        'position',
        'days_in_faction',
        'status_color',
        'status_description',
        'war_score',
        'ff_score',
        'estimated_stats',
        'ff_updated_at',
        'data',
    ];

    protected $casts = [
        'level' => 'integer',
        'war_score' => 'integer',
        'ff_score' => 'decimal:2',
        'ff_updated_at' => 'datetime',
        'data' => 'array',
    ];

    public function war()
    {
        return $this->belongsTo(RankedWar::class, 'war_id', 'war_id');
    }

    public function faction()
    {
        return $this->belongsTo(FactionMember::class, 'player_id', 'player_id');
    }
}
