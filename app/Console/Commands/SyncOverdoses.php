<?php

namespace App\Console\Commands;

use App\Models\FactionSettings;
use App\Models\OverdoseCount;
use App\Models\OverdoseEvent;
use App\Models\DataRefreshLog;
use App\Services\TornApiService;
use App\Services\WarService;
use Illuminate\Console\Command;

class SyncOverdoses extends Command
{
    protected $signature = 'torn:sync-overdoses {faction_id?} {--force : Force sync even during active war}';
    protected $description = 'Sync overdose counts from Torn API and detect new overdoses';

    public function handle(TornApiService $tornApi): int
    {
        if (!WarService::canFetchNonEssentialData() && !$this->option('force')) {
            $this->warn('Overdose sync skipped: Active war detected. Non-essential API calls are disabled.');
            return Command::SUCCESS;
        }

        $factionId = $this->argument('faction_id') ?? FactionSettings::value('faction_id');

        if (!$factionId) {
            $this->error('No faction ID provided or configured.');
            return Command::FAILURE;
        }

        $this->info("Syncing overdose counts for faction {$factionId}...");
        $log = DataRefreshLog::logStart('overdoses');

        $data = $tornApi->getFactionContributors($factionId);

        if (!$data || !isset($data['contributors']['drugoverdoses'])) {
            $this->error('Failed to fetch overdose data.');
            $log->markFailed('No data returned');
            return Command::FAILURE;
        }

        $overdoses = $data['contributors']['drugoverdoses'];
        
        $currentCounts = OverdoseCount::where('faction_id', $factionId)
            ->get()
            ->keyBy('player_id');
        
        $newOverdoses = [];
        $updated = 0;
        $unchanged = 0;

        foreach ($overdoses as $playerId => $data) {
            $count = $data['contributed'] ?? 0;
            $inFaction = $data['in_faction'] ?? 0;

            if ($inFaction !== 1) {
                continue;
            }

            $existing = $currentCounts->get($playerId);

            if (!$existing) {
                OverdoseCount::create([
                    'faction_id' => $factionId,
                    'player_id' => $playerId,
                    'count' => $count,
                    'updated_at' => now(),
                ]);
            } elseif ($existing->count != $count) {
                $oldCount = $existing->count;
                $existing->count = $count;
                $existing->updated_at = now();
                $existing->save();
                $updated++;
                
                $newOverdoses[] = [
                    'player_id' => $playerId,
                    'old_count' => $oldCount,
                    'new_count' => $count,
                    'difference' => $count - $oldCount,
                ];

                OverdoseEvent::create([
                    'faction_id' => $factionId,
                    'player_id' => $playerId,
                    'count_at_time' => $count,
                    'detected_at' => now(),
                ]);
            } else {
                $unchanged++;
            }
        }

        $memberIds = array_keys(array_filter($overdoses, fn($d) => ($d['in_faction'] ?? 0) === 1));
        
        $removed = OverdoseCount::where('faction_id', $factionId)
            ->whereNotIn('player_id', $memberIds)
            ->delete();

        if ($removed > 0) {
            $this->info("Removed {$removed} member(s) who are no longer in the faction.");
        }

        if (!empty($newOverdoses)) {
            $this->warn("Detected {$updated} member(s) with new overdoses:");
            foreach ($newOverdoses as $od) {
                $this->warn("  Player {$od['player_id']}: {$od['old_count']} → {$od['new_count']} (+{$od['difference']})");
            }
        } else {
            $this->info("No new overdoses detected ({$unchanged} members unchanged).");
        }

        $log->markComplete($updated);
        return Command::SUCCESS;
    }
}
