<?php

namespace App\Console\Commands;

use App\Models\FactionSettings;
use App\Models\RankedWar;
use App\Models\WarMember;
use App\Models\DataRefreshLog;
use App\Services\TornApiService;
use App\Services\FFScouterService;
use Illuminate\Console\Command;

class SyncRankedWars extends Command
{
    protected $signature = 'torn:sync-wars {faction_id?}';
    protected $description = 'Sync ranked wars from Torn API';

    public function handle(TornApiService $tornApi, FFScouterService $ffscouter): int
    {
        $factionId = $this->argument('faction_id') ?? FactionSettings::value('faction_id');

        if (!$factionId) {
            $this->error('No faction ID provided or configured.');
            return Command::FAILURE;
        }

        $this->info("Syncing ranked wars for faction {$factionId}...");
        $log = DataRefreshLog::logStart('ranked_wars');

        $data = $tornApi->getRankedWars($factionId);

        if (!$data || !isset($data['rankedwars'])) {
            $this->error('Failed to fetch ranked wars.');
            return Command::FAILURE;
        }

        $warCount = 0;
        $memberCount = 0;

        foreach ($data['rankedwars'] as $warId => $war) {
            $factions = $war['factions'] ?? [];
            $warInfo = $war['war'] ?? [];

            $opponentId = null;
            $opponentName = null;
            $scoreOurs = null;
            $scoreThem = null;

            foreach ($factions as $oppId => $oppData) {
                if ((string)$oppId !== (string)$factionId) {
                    $opponentId = $oppId;
                    $opponentName = $oppData['name'] ?? null;
                    $scoreThem = $oppData['score'] ?? null;
                } else {
                    $scoreOurs = $oppData['score'] ?? 0;
                }
            }

            $status = 'pending';
            if (isset($warInfo['winner']) && $warInfo['winner'] > 0) {
                $status = ((string)$warInfo['winner'] === (string)$factionId) ? 'won' : 'lost';
            } elseif ($scoreOurs > 0 || $scoreThem > 0) {
                $status = 'in progress';
            }

            RankedWar::updateOrCreate(
                [
                    'war_id' => $warId,
                    'faction_id' => $factionId,
                ],
                [
                    'opponent_faction_id' => $opponentId,
                    'opponent_faction_name' => $opponentName,
                    'status' => $status,
                    'start_date' => isset($warInfo['start']) ? now()->createFromTimestamp($warInfo['start']) : null,
                    'end_date' => isset($warInfo['end']) && $warInfo['end'] > 0 ? now()->createFromTimestamp($warInfo['end']) : null,
                    'score_ours' => $scoreOurs,
                    'score_them' => $scoreThem,
                    'data' => $war,
                ]
            );

            foreach ($factions as $oppFactionId => $oppData) {
                $memberData = $tornApi->getFactionMembers((int)$oppFactionId);
                if ($memberData && isset($memberData['members'])) {
                    $playerIds = array_keys($memberData['members']);
                    
                    $ffResults = $ffscouter->getStats($playerIds);
                    $ffIndex = [];
                    foreach ($ffResults as $ff) {
                        $ffIndex[$ff['player_id']] = $ff;
                    }
                    
                    $onlineResults = $tornApi->getPlayersOnlineStatus($playerIds);
                    
                    foreach ($memberData['members'] as $playerId => $member) {
                        $warScore = $oppData['score'] ?? 0;
                        
                        $ffData = $ffIndex[$playerId] ?? null;
                        $ffScore = $ffData['fair_fight'] ?? null;
                        $estimatedStats = $ffData['bs_estimate_human'] ?? null;
                        $ffUpdatedAt = isset($ffData['last_updated']) ? now()->createFromTimestamp($ffData['last_updated']) : null;
                        
                        $onlineData = $onlineResults[$playerId] ?? ['online_status' => 'offline', 'online_description' => null];
                        
                        WarMember::updateOrCreate(
                            [
                                'war_id' => $warId,
                                'faction_id' => $oppFactionId,
                                'player_id' => $playerId,
                            ],
                            [
                                'name' => $member['name'] ?? null,
                                'level' => $member['level'] ?? 1,
                                'rank' => $member['rank'] ?? null,
                                'position' => $member['position'] ?? null,
                                'days_in_faction' => $member['days_in_faction'] ?? null,
                                'status_color' => $member['status']['color'] ?? null,
                                'status_description' => $member['status']['description'] ?? null,
                                'war_score' => $warScore,
                                'ff_score' => $ffScore,
                                'estimated_stats' => $estimatedStats,
                                'ff_updated_at' => $ffUpdatedAt,
                                'online_status' => $onlineData['online_status'],
                                'online_description' => $onlineData['online_description'],
                                'data' => $member,
                            ]
                        );
                        $memberCount++;
                    }
                }
            }

            $warCount++;
        }

        $this->info("Synced {$warCount} ranked wars with {$memberCount} member records.");
        
        $log->markComplete($memberCount);
        return Command::SUCCESS;
    }
}
