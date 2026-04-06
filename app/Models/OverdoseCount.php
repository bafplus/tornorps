<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OverdoseCount extends Model
{
    protected $table = 'overdose_counts';
    
    public $timestamps = false;
    
    protected $fillable = [
        'faction_id',
        'player_id',
        'count',
        'updated_at',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];
}
