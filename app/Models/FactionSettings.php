<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactionSettings extends Model
{
    protected $fillable = [
        'faction_id',
        'faction_name',
        'torn_api_key',
        'ffscouter_api_key',
        'auto_sync_enabled',
        'sync_settings',
        'base_domain',
        'travel_method',
    ];

    protected $casts = [
        'auto_sync_enabled' => 'boolean',
        'sync_settings' => 'array',
    ];

    protected $hidden = [
        'torn_api_key',
        'ffscouter_api_key',
    ];
}
