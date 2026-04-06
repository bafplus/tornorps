<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OverdoseEvent extends Model
{
    protected $table = 'overdose_events';
    
    public $timestamps = true;
    
    protected $fillable = [
        'faction_id',
        'player_id',
        'count_at_time',
        'drug_id',
        'detected_at',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
    ];
}
