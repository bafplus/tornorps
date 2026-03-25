<?php

namespace App\Services;

use App\Models\FactionSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TornApiService
{
    private ?string $apiKey = null;
    private string $baseUrl = 'https://api.torn.com';
    private int $rateLimit = 100;

    public function __construct()
    {
        // Skip loading API key if tables don't exist yet (during setup)
        if (!\Schema::hasTable('faction_settings')) {
            return;
        }

        try {
            $settings = FactionSettings::first();
            if ($settings && $settings->torn_api_key) {
                $this->apiKey = $settings->torn_api_key;
            }
        } catch (\Exception $e) {
            $this->apiKey = null;
        }
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function get(string $endpoint, array $params = [], ?string $apiKey = null): ?array
    {
        $key = $apiKey ?? $this->apiKey;
        $cacheKey = 'torn_api_' . md5($endpoint . json_encode($params) . $key);

        $cacheTtl = $this->getCacheTtl($endpoint);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($endpoint, $params, $key) {
            $this->logApiCall($endpoint, $params);

            $response = Http::timeout(10)
                ->get("{$this->baseUrl}/{$endpoint}", array_merge($params, [
                    'key' => $key
                ]));

            if ($response->failed()) {
                Log::error('Torn API Error', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            $data = $response->json();

            if (isset($data['error'])) {
                Log::error('Torn API Error', [
                    'endpoint' => $endpoint,
                    'error' => $data['error']
                ]);
                return null;
            }

            return $data;
        });
    }

    public function getFaction(int $factionId, string $selections = 'info,members,stats,wars,rankedwars'): ?array
    {
        return $this->get("faction/{$factionId}", ['selections' => $selections]);
    }

    public function getFactionMembers(int $factionId): ?array
    {
        $data = $this->get("faction/{$factionId}");
        if ($data && isset($data['members'])) {
            return ['members' => $data['members']];
        }
        return $data;
    }

    public function getFactionChain(int $factionId): ?array
    {
        $data = $this->getNoCache("faction/{$factionId}", ['selections' => 'chain']);
        if ($data && isset($data['chain'])) {
            return $data['chain'];
        }
        return null;
    }

    public function getRankedWars(int $factionId, bool $noCache = false): ?array
    {
        $data = $noCache ? $this->getNoCache("faction/{$factionId}") : $this->get("faction/{$factionId}");
        if ($data && isset($data['ranked_wars'])) {
            return ['rankedwars' => $data['ranked_wars']];
        }
        return $data;
    }
    
    public function getNoCache(string $endpoint, array $params = [], ?string $apiKey = null): ?array
    {
        $key = $apiKey ?? $this->apiKey;
        
        $response = Http::timeout(10)
            ->get("{$this->baseUrl}/{$endpoint}", array_merge($params, [
                'key' => $key
            ]));

        if ($response->failed()) {
            Log::error('Torn API Error (no cache)', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        }

        $data = $response->json();

        if (isset($data['error'])) {
            Log::error('Torn API Error (no cache)', [
                'endpoint' => $endpoint,
                'error' => $data['error']
            ]);
            return null;
        }

        return $data;
    }

    public function getRankedWarReport(int $factionId, int $warId): ?array
    {
        return $this->get("faction/{$factionId}", [
            'selections' => 'rankedwarreport',
            'war' => $warId
        ]);
    }

    public function getPlayer(int $playerId, string $selections = 'profile,battlestats,personalstats,attacks', ?string $apiKey = null): ?array
    {
        return $this->get("user/{$playerId}", ['selections' => $selections], $apiKey);
    }

    public function getPlayersOnlineStatus(array $playerIds): array
    {
        $results = [];
        foreach ($playerIds as $playerId) {
            $data = $this->getFresh("user/{$playerId}", ['selections' => 'profile']);
            if ($data && isset($data['last_action']['status'])) {
                $results[$playerId] = [
                    'online_status' => $data['last_action']['status'] ?? 'Offline',
                    'online_description' => $data['last_action']['relative'] ?? null,
                ];
            } else {
                $results[$playerId] = [
                    'online_status' => 'Offline',
                    'online_description' => null,
                ];
            }
        }
        return $results;
    }

    public function getFresh(string $endpoint, array $params = [], ?string $apiKey = null): ?array
    {
        $key = $apiKey ?? $this->apiKey;

        $this->logApiCall($endpoint, $params);

        $response = Http::timeout(10)
            ->get("{$this->baseUrl}/{$endpoint}", array_merge($params, [
                'key' => $key
            ]));

        if ($response->failed()) {
            Log::error('Torn API Error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        }

        $data = $response->json();

        if (isset($data['error'])) {
            Log::error('Torn API Error', [
                'endpoint' => $endpoint,
                'error' => $data['error']
            ]);
            return null;
        }

        return $data;
    }

    public function getWars(int $factionId): ?array
    {
        return $this->get("faction/{$factionId}", ['selections' => 'wars']);
    }

public function getWarfare(int $factionId): ?array
{
return $this->get("faction/{$factionId}", ['selections' => 'warfare']);
}

public function getFactionAttacks(int $factionId, ?string $apiKey = null, ?int $from = null, ?int $to = null): ?array
{
$params = ['selections' => 'attacks'];
if ($from) {
$params['from'] = $from;
}
if ($to) {
$params['to'] = $to;
}
return $this->get("faction/{$factionId}", $params, $apiKey);
}

public function getUserAttacksFull(string $apiKey, int $limit = 1000): ?array
{
$response = Http::timeout(10)
->get("{$this->baseUrl}/v2/user/attacksfull", [
'key' => $apiKey,
'limit' => $limit
]);

if ($response->failed()) {
Log::error('Torn V2 API Error', [
'endpoint' => "v2/user/attacksfull",
'status' => $response->status(),
'body' => $response->body()
]);
return null;
}

$data = $response->json();

if (isset($data['error'])) {
Log::error('Torn V2 API Error', [
'endpoint' => "v2/user/attacksfull",
'error' => $data['error']
]);
return null;
}

return $data;
}

    public function getAllRankedWars(): ?array
    {
        return $this->get("torn", ['selections' => 'rankedwars']);
    }

    public function clearCache(string $endpoint = null): void
    {
        if ($endpoint) {
            Cache::flush();
        } else {
            Cache::flush();
        }
    }

    private function getCacheTtl(string $endpoint): int
    {
        $ttls = config('services.torn.cache_ttl', [
            'faction' => 900,
            'members' => 900,
            'wars' => 300,
            'ranked_wars' => 300,
            'player' => 1800,
        ]);

        if (str_contains($endpoint, 'members')) {
            return $ttls['members'] ?? 900;
        }
        if (str_contains($endpoint, 'wars') || str_contains($endpoint, 'war')) {
            return $ttls['wars'] ?? 300;
        }
        if (str_contains($endpoint, 'ranked')) {
            return $ttls['ranked_wars'] ?? 300;
        }
        if (str_contains($endpoint, 'user') || str_contains($endpoint, 'player')) {
            return $ttls['player'] ?? 1800;
        }

        return 300;
    }

    private function logApiCall(string $endpoint, array $params): void
    {
        Log::info('Torn API Call', [
            'endpoint' => $endpoint,
            'params' => array_diff_key($params, ['key' => ''])
        ]);
    }
}
