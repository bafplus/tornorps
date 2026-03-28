<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('torn:sync-faction')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('torn:sync-wars')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('torn:sync-active')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

$schedule->command('torn:sync-attacks')
        ->everyMinute()
        ->withoutOverlapping()
        ->runInBackground();

        $schedule->command('torn:sync-chains')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('torn:check-faction-membership')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->call(function () {
            \Illuminate\Support\Facades\Artisan::call('torn:sync-stocks');
        })
            ->dailyAt('00:15')
            ->withoutOverlapping()
            ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
