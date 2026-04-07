<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\RankedWar;

class ScheduledJob extends Model
{
    protected $fillable = [
        'command',
        'description',
        'enabled',
        'cron_expression',
        'war_mode_only',
        'war_enabled',
        'war_cron',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'war_mode_only' => 'boolean',
        'war_enabled' => 'boolean',
    ];

    public static function isWarMode(): bool
    {
        return RankedWar::where('status', 'in progress')->exists();
    }

    public function getEffectiveCron(): ?string
    {
        if ($this->war_mode_only && self::isWarMode()) {
            return $this->war_cron;
        }
        
        if ($this->war_mode_only && !self::isWarMode()) {
            return null;
        }

        return $this->cron_expression;
    }

    public function shouldRun(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $cron = $this->getEffectiveCron();
        
        if ($this->war_mode_only && self::isWarMode() && !$this->war_enabled) {
            return false;
        }

        return $cron !== null;
    }
}
