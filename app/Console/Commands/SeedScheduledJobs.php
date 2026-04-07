<?php

namespace App\Console\Commands;

use App\Models\ScheduledJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SeedScheduledJobs extends Command
{
    protected $signature = 'jobs:seed';
    protected $description = 'Seed scheduled jobs from Kernel.php schedule configuration';

    protected array $jobDefinitions = [
        'torn:sync-faction' => [
            'description' => 'Sync faction data (members, stats, info)',
            'war_mode_only' => false,
            'default_cron' => '*/5 * * * *',
        ],
        'torn:sync-wars' => [
            'description' => 'Sync ranked wars list',
            'war_mode_only' => true,
            'default_cron' => '*/10 * * * *',
            'war_cron' => '*/5 * * * *',
        ],
        'torn:sync-active' => [
            'description' => 'Sync active war details and scores',
            'war_mode_only' => true,
            'default_cron' => '*/10 * * * *',
            'war_cron' => '*/1 * * * *',
        ],
        'torn:sync-attacks' => [
            'description' => 'Sync war attacks data',
            'war_mode_only' => true,
            'default_cron' => '*/10 * * * *',
            'war_cron' => '*/1 * * * *',
        ],
        'torn:sync-chains' => [
            'description' => 'Sync war chain data',
            'war_mode_only' => true,
            'default_cron' => '*/10 * * * *',
            'war_cron' => '*/1 * * * *',
        ],
        'torn:check-faction-membership' => [
            'description' => 'Check faction membership and sync new members',
            'war_mode_only' => false,
            'default_cron' => '0 * * * *',
        ],
        'torn:sync-stocks' => [
            'description' => 'Sync market stocks data',
            'war_mode_only' => false,
            'default_cron' => '0 0 * * *',
        ],
        'torn:sync-items' => [
            'description' => 'Sync item market data',
            'war_mode_only' => false,
            'default_cron' => '0 0 * * *',
        ],
    ];

    public function handle(): int
    {
        $created = 0;
        $updated = 0;

        foreach ($this->jobDefinitions as $command => $config) {
            $exists = ScheduledJob::where('command', $command)->exists();

            $data = [
                'description' => $config['description'],
                'enabled' => true,
                'cron_expression' => $config['default_cron'],
                'war_mode_only' => $config['war_mode_only'],
                'war_enabled' => true,
                'war_cron' => $config['war_cron'] ?? null,
            ];

            if ($exists) {
                ScheduledJob::where('command', $command)->update($data);
                $updated++;
                $this->info("Updated: {$command}");
            } else {
                ScheduledJob::create(array_merge(['command' => $command], $data));
                $created++;
                $this->info("Created: {$command}");
            }
        }

        $this->newLine();
        $this->info("Seeding complete: {$created} created, {$updated} updated.");

        return Command::SUCCESS;
    }
}
