<?php

namespace App\Services;

use App\Models\RankedWar;

class WarService
{
    public static function hasActiveWar(): bool
    {
        return RankedWar::whereIn('status', ['accepted', 'in progress'])
            ->where('faction_id', self::getFactionId())
            ->exists();
    }

    public static function hasPendingWar(): bool
    {
        return RankedWar::where('status', 'pending')
            ->where('faction_id', self::getFactionId())
            ->exists();
    }

    public static function isWarActive(): bool
    {
        return self::hasActiveWar();
    }

    public static function shouldThrottleApiCalls(): bool
    {
        return self::hasActiveWar();
    }

    public static function canFetchNonEssentialData(): bool
    {
        return !self::hasActiveWar();
    }

    public static function getActiveWar()
    {
        return RankedWar::whereIn('status', ['accepted', 'in progress'])
            ->where('faction_id', self::getFactionId())
            ->first();
    }

    protected static function getFactionId(): ?int
    {
        return \App\Models\FactionSettings::value('faction_id');
    }
}
