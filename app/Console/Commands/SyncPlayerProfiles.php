<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\RankedWar;

class SyncPlayerProfiles extends Command
{
    protected $signature = 'torn:sync-profiles {--force : Force update all profiles}';
    protected $description = 'Sync player profiles from Torn API';

    protected $staleDays = 3;
    protected $batchSize = 10;
    protected $processed = 0;
    protected $updated = 0;
    protected $new = 0;
    protected $errors = 0;
    protected $rateLimited = false;

    public function handle(): int
    {
        $this->info('Starting player profile sync...');

        $force = $this->option('force');
        
        // Get target player IDs
        $targetIds = $this->getTargetPlayerIds();
        
        if (empty($targetIds)) {
            $this->warn('No players to sync');
            return 0;
        }

        $this->info('Found ' . count($targetIds) . ' players to sync');

        // Get API keys
        $apiKeys = $this->getApiKeys();
        
        if (empty($apiKeys)) {
            $this->error('No API keys available');
            return 1;
        }

        $this->info('Using ' . count($apiKeys) . ' API key(s)');

        // Process in batches with key rotation
        $chunks = array_chunk($targetIds, $this->batchSize);
        
        foreach ($chunks as $batch) {
            foreach ($batch as $playerId) {
                $apiKey = $apiKeys[array_rand($apiKeys)];
                
                $this->processPlayer($playerId, $apiKey, $force);
                
                $this->processed++;
                
                // Rate limit handling
                if ($this->rateLimited) {
                    $this->warn('Rate limited, stopping for this run');
                    break 2;
                }
            }
        }

        $this->info("Sync complete: {$this->processed} processed, {$this->new} new, {$this->updated} updated, {$this->errors} errors");
        
        return 0;
    }

    protected function getTargetPlayerIds(): array
    {
        $settings = DB::table('faction_settings')->first();
        $factionId = $settings->faction_id ?? 0;
        
        // Get faction members
        $memberIds = DB::table('war_members')
            ->where('faction_id', $factionId)
            ->pluck('player_id')
            ->toArray();

        // Get opponents from active/planned wars
        $activeWars = DB::table('ranked_wars')
            ->whereIn('status', ['pending', 'accepted', 'in progress'])
            ->pluck('opponent_faction_id')
            ->toArray();

        $opponentIds = DB::table('war_members')
            ->whereIn('faction_id', $activeWars)
            ->pluck('player_id')
            ->toArray();

        // Combine and dedupe
        $targetIds = array_unique(array_merge($memberIds, $opponentIds));

        // Filter: only new or stale (>3 days)
        if (empty($targetIds)) {
            return [];
        }

        // Check what's already synced
        $synced = DB::table('player_profiles')
            ->whereIn('player_id', $targetIds)
            ->whereNotNull('last_synced_at')
            ->get()
            ->keyBy('player_id');

        // Filter new or stale
        $cutoff = now()->subDays($this->staleDays);
        
        $result = [];
        foreach ($targetIds as $id) {
            $profile = $synced[$id] ?? null;
            
            if (!$profile) {
                // New player
                $result[] = $id;
            } elseif ($force || $profile->last_synced_at->lt($cutoff)) {
                // Stale
                $result[] = $id;
            }
        }

        return $result;
    }

    protected function getApiKeys(): array
    {
        return User::whereNotNull('torn_api_key')
            ->where('torn_api_key', '!=', '')
            ->pluck('torn_api_key')
            ->toArray();
    }

    protected function processPlayer(int $playerId, string $apiKey, bool $force): void
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $apiKey,
                'Accept' => 'application/json',
            ])->get("https://api.torn.com/v2/user/{$playerId}/profile", [
                'statustags' => 'true',
            ]);

            if ($response->status() === 429) {
                $this->rateLimited = true;
                return;
            }

            if ($response->failed()) {
                $this->error("Failed for player {$playerId}: " . $response->status());
                $this->errors++;
                return;
            }

            $data = $response->json();
            $profile = $data['profile'] ?? null;

            if (!$profile) {
                $this->error("No profile data for player {$playerId}");
                $this->errors++;
                return;
            }

            // Determine if this is from a war opponent
            $isFromWar = $this->isWarOpponent($playerId);

            // Build record
            $record = [
                'player_id' => $profile['id'],
                'name' => $profile['name'] ?? null,
                'level' => $profile['level'] ?? 1,
                'rank' => $profile['rank'] ?? null,
                'title' => $profile['title'] ?? null,
                'age' => $profile['age'] ?? null,
                'signed_up' => $profile['signed_up'] ?? null,
                'faction_id' => $profile['faction_id'] ?? null,
                'honor_id' => $profile['honor_id'] ?? null,
                'property_id' => $profile['property']['id'] ?? null,
                'property_name' => $profile['property']['name'] ?? null,
                'donator_status' => $profile['donator_status'] ?? null,
                'image' => $profile['image'] ?? null,
                'gender' => $profile['gender'] ?? null,
                'role' => $profile['role'] ?? null,
                'revivable' => $profile['revivable'] ?? false,
                'status_description' => $profile['status']['description'] ?? null,
                'status_details' => $profile['status']['details'] ?? null,
                'status_state' => $profile['status']['state'] ?? null,
                'status_color' => $profile['status']['color'] ?? null,
                'status_until' => $profile['status']['until'] ?? null,
                'spouse_id' => $profile['spouse']['id'] ?? null,
                'spouse_name' => $profile['spouse']['name'] ?? null,
                'spouse_status' => $profile['spouse']['status'] ?? null,
                'awards' => $profile['awards'] ?? 0,
                'friends' => $profile['friends'] ?? 0,
                'enemies' => $profile['enemies'] ?? 0,
                'forum_posts' => $profile['forum_posts'] ?? 0,
                'karma' => $profile['karma'] ?? 0,
                'life_current' => $profile['life']['current'] ?? 0,
                'life_maximum' => $profile['life']['maximum'] ?? 0,
                'last_action_status' => $profile['last_action']['status'] ?? null,
                'last_action_timestamp' => $profile['last_action']['timestamp'] ?? null,
                'from_war' => $isFromWar ? 1 : 0,
                'data' => json_encode($profile),
                'last_synced_at' => now(),
                'updated_at' => now(),
            ];

            // Insert or update
            $existing = DB::table('player_profiles')->where('player_id', $playerId)->first();
            
            if ($existing) {
                DB::table('player_profiles')->where('player_id', $playerId)->update($record);
                $this->updated++;
            } else {
                $record['created_at'] = now();
                $record['name'] = $profile['name'] ?? 'Unknown';
                DB::table('player_profiles')->insert($record);
                $this->new++;
            }

        } catch (\Exception $e) {
            $this->error("Error for player {$playerId}: " . $e->getMessage());
            $this->errors++;
        }
    }

    protected function isWarOpponent(int $playerId): bool
    {
        $activeWars = DB::table('ranked_wars')
            ->whereIn('status', ['pending', 'accepted', 'in progress'])
            ->pluck('opponent_faction_id')
            ->toArray();

        if (empty($activeWars)) {
            return false;
        }

        $member = DB::table('war_members')
            ->where('player_id', $playerId)
            ->whereIn('faction_id', $activeWars)
            ->first();

        return $member !== null;
    }
}