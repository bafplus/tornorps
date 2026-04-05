<?php

namespace App\Console\Commands;

use App\Models\FactionSettings;
use App\Models\OverdoseCount;
use App\Models\OverdoseEvent;
use App\Models\FactionMember;
use App\Services\TornApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncOverdoseHistory extends Command
{
    protected $signature = 'torn:sync-overdose-history {faction_id?}';
    protected $description = 'Fetch historical overdose events for faction members';

    public function handle(TornApiService $tornApi): int
    {
        $factionId = $this->argument('faction_id') ?? FactionSettings::value('faction_id');

        if (!$factionId) {
            $this->error('No faction ID provided or configured.');
            return Command::FAILURE;
        }

        $settings = FactionSettings::first();
        $apiKey = $settings->torn_api_key;

        $members = FactionMember::where('faction_id', $factionId)->get();
        $this->info("Fetching overdose history for {$members->count()} members...");

        $fetched = 0;
        $added = 0;

        foreach ($members as $member) {
            $this->line("Checking member {$member->player_id} ({$member->name})...");

            $response = Http::timeout(10)
                ->get("https://api.torn.com/v2/user/{$member->player_id}", [
                    'selections' => 'events',
                    'key' => $apiKey,
                ]);

            if ($response->failed()) {
                $this->warn("  Failed to fetch events for {$member->player_id}");
                continue;
            }

            $events = $response->json()['events'] ?? [];
            $fetched++;

            $overdoseCount = 0;
            foreach ($events as $event) {
                $eventText = $event['event'] ?? '';
                if (preg_match('/overdosed?/i', $eventText)) {
                    $overdoseCount++;

                    $exists = OverdoseEvent::where('faction_id', $factionId)
                        ->where('player_id', $member->player_id)
                        ->where('detected_at', date('Y-m-d H:i:s', $event['timestamp']))
                        ->exists();

                    if (!$exists) {
                        OverdoseEvent::create([
                            'faction_id' => $factionId,
                            'player_id' => $member->player_id,
                            'count_at_time' => $overdoseCount,
                            'detected_at' => date('Y-m-d H:i:s', $event['timestamp']),
                        ]);
                        $added++;
                        $this->info("  Found overdose: {$member->name} at " . date('Y-m-d H:i:s', $event['timestamp']));
                    }
                }
            }

            OverdoseCount::updateOrCreate(
                ['faction_id' => $factionId, 'player_id' => $member->player_id],
                ['count' => $overdoseCount, 'updated_at' => now()]
            );

            usleep(200000);
        }

        $this->info("Processed {$fetched} members, added {$added} overdose events.");
        return Command::SUCCESS;
    }
}
