<?php

namespace App\Console\Commands;

use App\Models\FactionSettings;
use App\Models\FactionMember;
use App\Models\DataRefreshLog;
use App\Services\TornApiService;
use App\Services\FFScouterService;
use Illuminate\Console\Command;

class SyncFactionMembers extends Command
{
    protected $signature = 'torn:sync-members {faction_id?}';
    protected $description = 'Sync faction members from Torn API';

    public function handle(TornApiService $tornApi, FFScouterService $ffscouter): int
    {
        $factionId = $this->argument('faction_id') ?? FactionSettings::value('faction_id');

        if (!$factionId) {
            $this->error('No faction ID provided or configured.');
            return Command::FAILURE;
        }

        $this->info("Syncing members for faction {$factionId}...");
        $log = DataRefreshLog::logStart('faction_members');

        $data = $tornApi->getFactionMembers($factionId);

        if (!$data || !isset($data['members'])) {
            $this->error('Failed to fetch faction members.');
            return Command::FAILURE;
        }

        // Get FF scores - first check war_members, then use FF Scouter for missing
        $playerIds = array_keys($data['members']);
        
        // Get existing FF data from war_members table
        $existingFF = \App\Models\WarMember::whereIn('player_id', $playerIds)
            ->whereNotNull('ff_score')
            ->where('ff_score', '>', 0)
            ->get()
            ->unique('player_id')
            ->keyBy('player_id');

        // Only fetch FF Scouter for members without data
        $missingIds = array_filter($playerIds, fn($id) => !$existingFF->has($id));
        $ffIndex = [];
        
        if (!empty($missingIds)) {
            $ffResults = $ffscouter->getStats($missingIds);
            foreach ($ffResults as $ff) {
                $ffIndex[$ff['player_id']] = $ff;
            }
        }

        $count = 0;
        foreach ($data['members'] as $playerId => $member) {
            // Use existing data from war_members or new FF data
            if ($existingFF->has($playerId)) {
                $ffData = $existingFF->get($playerId);
                $ffScore = $ffData->ff_score;
                $estimatedStats = $ffData->estimated_stats;
            } elseif (isset($ffIndex[$playerId])) {
                $ffData = $ffIndex[$playerId];
                $ffScore = $ffData['fair_fight'] ?? null;
                $estimatedStats = $ffData['bs_estimate_human'] ?? null;
            } else {
                $ffScore = null;
                $estimatedStats = null;
            }
            
            FactionMember::updateOrCreate(
                [
                    'faction_id' => $factionId,
                    'player_id' => $playerId,
                ],
                [
                    'name' => $member['name'] ?? null,
                    'level' => $member['level'] ?? 1,
                    'rank' => $member['rank'] ?? null,
                    'position' => $member['position'] ?? null,
                    'days_in_faction' => $member['days_in_faction'] ?? null,
                    'status_description' => $member['status']['description'] ?? null,
                    'status_color' => $member['status']['color'] ?? null,
                    'online_status' => $member['last_action']['status'] ?? null,
                    'status_changed_at' => isset($member['status']['until']) && $member['status']['until'] > 0 
                        ? \Carbon\Carbon::createFromTimestamp($member['status']['until']) 
                        : null,
                    'ff_score' => $ffScore,
                    'estimated_stats' => $estimatedStats,
                    'data' => $member,
                    'last_synced_at' => now(),
                ]
            );
            $count++;
        }

        $skipped = count($missingIds);
        $reused = $count - $skipped;
        $this->info("Synced {$count} members ({$reused} from cache, {$skipped} from FF Scouter).");
        
        $log->markComplete($count);
        return Command::SUCCESS;
    }
}
