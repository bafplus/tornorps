<?php

namespace App\Console\Commands;

use App\Models\FactionSettings;
use App\Models\RankedWar;
use App\Models\WarMember;
use App\Models\DataRefreshLog;
use App\Services\TornApiService;
use App\Services\FFScouterService;
use Illuminate\Console\Command;

class SyncActiveWars extends Command
{
    protected $signature = 'torn:sync-active';
    protected $description = 'Sync active ranked wars - runs every minute for near real-time updates';

    public function handle(TornApiService $tornApi, FFScouterService $ffscouter): int
    {
        $factionId = FactionSettings::value('faction_id');

        if (!$factionId) {
            return Command::FAILURE;
        }

        $activeWars = RankedWar::whereIn('status', ['in progress', 'pending'])->get();

        $log = DataRefreshLog::logStart('active_wars');

        if ($activeWars->isEmpty()) {
            $log->update(['status' => 'skipped', 'completed_at' => now()]);
            return Command::SUCCESS;
        }

        $log = DataRefreshLog::logStart('active_wars');
        $this->info("Syncing {$activeWars->count()} active war(s)...");

        foreach ($activeWars as $war) {
            $this->syncWar($war, $factionId, $tornApi, $ffscouter);
        }

        $log->markComplete($activeWars->count());
        return Command::SUCCESS;
    }

    private function syncWar(RankedWar $war, int $factionId, TornApiService $tornApi, FFScouterService $ffscouter): void
    {
        $data = $tornApi->getRankedWars($factionId, true);

        if (!$data || !isset($data['rankedwars'][$war->war_id])) {
            return;
        }

        $warData = $data['rankedwars'][$war->war_id];
        $factions = $warData['factions'] ?? [];

        $scoreOurs = null;
        $scoreThem = null;

        foreach ($factions as $oppId => $oppData) {
            if ((string)$oppId === (string)$factionId) {
                $scoreOurs = $oppData['score'] ?? 0;
            } else {
                $scoreThem = $oppData['score'] ?? null;
            }
        }

        $status = 'pending';
        $warInfo = $warData['war'] ?? [];
        if (isset($warInfo['winner']) && $warInfo['winner'] > 0) {
            $status = ((string)$warInfo['winner'] === (string)$factionId) ? 'won' : 'lost';
        } elseif ($scoreOurs > 0 || $scoreThem > 0) {
            $status = 'in progress';
        }

$target = $warInfo['target'] ?? 1900;
        $warDataArray = $war->data ?? [];
        $warDataArray['war'] = array_merge($warDataArray['war'] ?? [], ['target' => $target]);

        $war->update([
            'status' => $status,
            'score_ours' => $scoreOurs,
            'score_them' => $scoreThem,
            'end_date' => isset($warInfo['end']) && $warInfo['end'] > 0
                ? now()->createFromTimestamp($warInfo['end'])
                : null,
            'data' => $warDataArray,
        ]);

        $warMemberIds = WarMember::where('war_id', $war->war_id)
            ->pluck('player_id', 'faction_id')
            ->toArray();

        foreach ($factions as $oppFactionId => $oppData) {
            $memberData = $tornApi->getFactionMembers((int)$oppFactionId);
            
            if ($memberData && isset($memberData['members'])) {
                $playerIds = array_keys($memberData['members']);
                $onlineResults = $tornApi->getPlayersOnlineStatus($playerIds);

                foreach ($memberData['members'] as $playerId => $member) {
                    $existingMember = WarMember::where('war_id', $war->war_id)
                        ->where('faction_id', $oppFactionId)
                        ->where('player_id', $playerId)
                        ->first();

                    $onlineData = $onlineResults[$playerId] ?? ['online_status' => 'offline', 'online_description' => null];

                    $personalWarScore = $member['personalstats']['rankedwarhits'] ?? 0;
                    
                    $updateData = [
                        'name' => $member['name'] ?? null,
                        'level' => $member['level'] ?? 1,
                        'rank' => $member['rank'] ?? null,
                        'position' => $member['position'] ?? null,
                        'status_color' => $member['status']['color'] ?? null,
                        'status_description' => $member['status']['description'] ?? null,
                        'war_score' => $oppData['score'] ?? 0,
                        'online_status' => $onlineData['online_status'],
                        'online_description' => $onlineData['online_description'],
                        'data' => $member,
                        'last_synced_at' => now(),
                        'personal_war_score' => $personalWarScore,
                    ];

                    if ($existingMember) {
                        $newStatus = $member['status']['description'] ?? '';
                        $isTraveling = str_starts_with($newStatus, 'Traveling to') || str_starts_with($newStatus, 'Returning to Torn from');
                        $wasTraveling = str_starts_with($existingMember->status_description, 'Traveling to') || str_starts_with($existingMember->status_description, 'Returning to Torn from');
                        
                        if ($isTraveling && !$wasTraveling) {
                            $updateData['status_changed_at'] = now();
                        } elseif (!$isTraveling && $wasTraveling) {
                            $updateData['status_changed_at'] = null;
                        }
                        $existingMember->update($updateData);
                    } else {
                        $updateData['war_id'] = $war->war_id;
                        $updateData['faction_id'] = $oppFactionId;
                        $updateData['player_id'] = $playerId;
                        WarMember::create($updateData);
                    }
                }
            }
        }
    }
}
