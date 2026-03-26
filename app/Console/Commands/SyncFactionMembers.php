<?php

namespace App\Console\Commands;

use App\Models\FactionSettings;
use App\Models\FactionMember;
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

        $data = $tornApi->getFactionMembers($factionId);

        if (!$data || !isset($data['members'])) {
            $this->error('Failed to fetch faction members.');
            return Command::FAILURE;
        }

        // Get FF scores for all members
        $playerIds = array_keys($data['members']);
        $ffResults = $ffscouter->getStats($playerIds);
        $ffIndex = [];
        foreach ($ffResults as $ff) {
            $ffIndex[$ff['player_id']] = $ff;
        }

        $count = 0;
        foreach ($data['members'] as $playerId => $member) {
            $ffData = $ffIndex[$playerId] ?? null;
            
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
                    'ff_score' => $ffData['fair_fight'] ?? null,
                    'estimated_stats' => $ffData['bs_estimate_human'] ?? null,
                    'data' => $member,
                    'last_synced_at' => now(),
                ]
            );
            $count++;
        }

        $this->info("Synced {$count} members.");
        return Command::SUCCESS;
    }
}
