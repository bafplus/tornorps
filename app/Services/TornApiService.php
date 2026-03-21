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
        $settings = FactionSettings::first();
        if ($settings) {
            $this->apiKey = $settings->torn_api_key;
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

    public function getRankedWars(int $factionId): ?array
    {
        $data = $this->get("faction/{$factionId}");
        if ($data && isset($data['ranked_wars'])) {
            return ['rankedwars' => $data['ranked_wars']];
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

    public function getWars(int $factionId): ?array
    {
        return $this->get("faction/{$factionId}", ['selections' => 'wars']);
    }

    public function getWarfare(int $factionId): ?array
    {
        return $this->get("faction/{$factionId}", ['selections' => 'warfare']);
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
