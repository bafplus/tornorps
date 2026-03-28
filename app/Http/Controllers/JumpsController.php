<?php

namespace App\Http\Controllers;

use App\Services\TornApiService;
use App\Models\GymStatsHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class JumpsController extends Controller
{
    public function index(TornApiService $tornApi)
    {
        $user = Auth::user();
        $apiKey = $user->torn_api_key;
        $playerId = $user->torn_player_id;

        if (!$apiKey || !$playerId) {
            return view('jumps.index', [
                'error' => 'No API key or player ID found. Please add your Torn API key in Settings.',
                'bars' => null,
                'stats' => null,
                'gym' => null,
            ]);
        }

        // Fetch user bars (happy, energy) using V2
        $bars = $tornApi->getUserBars($apiKey);
        
        // Fetch user gym and battle stats using V1 (same as Gym Assistant)
        try {
            $response = Http::timeout(10)->get('https://api.torn.com/user/' . $playerId, [
                'key' => $apiKey,
                'selections' => 'gym,battlestats'
            ]);

            if ($response->failed()) {
                return view('jumps.index', [
                    'error' => 'Failed to fetch user data from Torn API.',
                    'bars' => null,
                    'stats' => null,
                    'gym' => null,
                ]);
            }

            $data = $response->json();

            if (isset($data['error'])) {
                $errorMsg = is_array($data['error']) ? ($data['error']['error'] ?? json_encode($data['error'])) : $data['error'];
                return view('jumps.index', [
                    'error' => 'API Error: ' . $errorMsg,
                    'bars' => null,
                    'stats' => null,
                    'gym' => null,
                ]);
            }
        } catch (\Exception $e) {
            return view('jumps.index', [
                'error' => 'Error: ' . $e->getMessage(),
                'bars' => null,
                'stats' => null,
                'gym' => null,
            ]);
        }

        // Extract battle stats
        $strength = $data['strength'] ?? 0;
        $defense = $data['defense'] ?? 0;
        $speed = $data['speed'] ?? 0;
        $dexterity = $data['dexterity'] ?? 0;
        $totalStats = $strength + $defense + $speed + $dexterity;

        // Extract gym info
        $gymId = $data['active_gym'] ?? null;
        $gymName = $this->getGymName($gymId);
        $gymData = $this->getGymData($gymId);

        // Extract bars
        $happy = $bars['happy'] ?? [];
        $energy = $bars['energy'] ?? [];
        $currentHappy = $happy['current'] ?? 0;
        $maxHappy = $happy['maximum'] ?? 0;

        return view('jumps.index', [
            'error' => null,
            'bars' => $bars,
            'stats' => [
                'strength' => $strength,
                'defense' => $defense,
                'speed' => $speed,
                'dexterity' => $dexterity,
            ],
            'current_happy' => $currentHappy,
            'max_happy' => $maxHappy,
            'current_energy' => $energy['current'] ?? 0,
            'max_energy' => $energy['maximum'] ?? 0,
            'strength' => $strength,
            'defense' => $defense,
            'speed' => $speed,
            'dexterity' => $dexterity,
            'total_stats' => $totalStats,
            'gym_name' => $gymName,
            'gym_id' => $gymId,
            'gym_energy_cost' => $gymData['energy_cost'],
            'gym_multiplier' => $gymData['multiplier'],
            'gym_str_bonus' => $gymData['str_bonus'],
            'gym_def_bonus' => $gymData['def_bonus'],
            'gym_spd_bonus' => $gymData['spd_bonus'],
            'gym_dex_bonus' => $gymData['dex_bonus'],
        ]);
    }

    private function getGymName(?int $gymId): string
    {
        if (!$gymId) return 'No Gym';
        
        $gymNames = [
            1 => 'Premier Fitness',
            2 => 'Average Joes',
            3 => "Woody's Workout",
            4 => 'Beach Bods',
            5 => 'Silver Gym',
            6 => 'Pour Femme',
            7 => 'Davies Den',
            8 => 'Global Gym',
            9 => 'Knuckle Heads',
            10 => 'Pioneer Fitness',
            11 => 'Anabolic Anomalies',
            12 => 'Core',
            13 => 'Racing Fitness',
            14 => 'Complete Cardio',
            15 => 'Legs, Bums and Tums',
            16 => 'Deep Burn',
            17 => 'Apollo Gym',
            18 => 'Gun Shop',
            19 => 'Force Training',
            20 => "Cha Cha's",
            21 => 'Atlas',
            22 => 'Last Round',
            23 => 'The Edge',
            24 => "George's",
            25 => 'Balboas Gym',
        ];
        
        return $gymNames[$gymId] ?? 'Unknown Gym';
    }

    private function getGymData(?int $gymId): array
    {
        // Gym data: energy cost per train, multiplier, and stat bonuses
        $gymData = [
            1  => ['energy_cost' => 10, 'multiplier' => 1.0, 'str_bonus' => 1.0, 'def_bonus' => 1.0, 'spd_bonus' => 1.0, 'dex_bonus' => 1.0],
            2  => ['energy_cost' => 15, 'multiplier' => 1.1, 'str_bonus' => 1.1, 'def_bonus' => 1.0, 'spd_bonus' => 1.0, 'dex_bonus' => 1.0],
            3  => ['energy_cost' => 20, 'multiplier' => 1.2, 'str_bonus' => 1.0, 'def_bonus' => 1.2, 'spd_bonus' => 1.0, 'dex_bonus' => 1.0],
            4  => ['energy_cost' => 25, 'multiplier' => 1.3, 'str_bonus' => 1.0, 'def_bonus' => 1.0, 'spd_bonus' => 1.3, 'dex_bonus' => 1.0],
            5  => ['energy_cost' => 30, 'multiplier' => 1.4, 'str_bonus' => 1.5, 'def_bonus' => 1.0, 'spd_bonus' => 1.0, 'dex_bonus' => 1.0],
            6  => ['energy_cost' => 35, 'multiplier' => 1.5, 'str_bonus' => 1.0, 'def_bonus' => 1.5, 'spd_bonus' => 1.0, 'dex_bonus' => 1.0],
            7  => ['energy_cost' => 40, 'multiplier' => 1.6, 'str_bonus' => 1.0, 'def_bonus' => 1.0, 'spd_bonus' => 1.5, 'dex_bonus' => 1.0],
            8  => ['energy_cost' => 50, 'multiplier' => 1.7, 'str_bonus' => 1.0, 'def_bonus' => 1.0, 'spd_bonus' => 1.0, 'dex_bonus' => 1.5],
            9  => ['energy_cost' => 60, 'multiplier' => 1.8, 'str_bonus' => 2.0, 'def_bonus' => 1.0, 'spd_bonus' => 1.0, 'dex_bonus' => 1.0],
            10 => ['energy_cost' => 70, 'multiplier' => 1.9, 'str_bonus' => 1.0, 'def_bonus' => 2.0, 'spd_bonus' => 1.0, 'dex_bonus' => 1.0],
            11 => ['energy_cost' => 80, 'multiplier' => 2.0, 'str_bonus' => 2.5, 'def_bonus' => 1.5, 'spd_bonus' => 1.0, 'dex_bonus' => 1.0],
            12 => ['energy_cost' => 90, 'multiplier' => 2.1, 'str_bonus' => 1.5, 'def_bonus' => 2.5, 'spd_bonus' => 1.0, 'dex_bonus' => 1.0],
            13 => ['energy_cost' => 100, 'multiplier' => 2.2, 'str_bonus' => 1.0, 'def_bonus' => 1.0, 'spd_bonus' => 2.5, 'dex_bonus' => 1.5],
            14 => ['energy_cost' => 120, 'multiplier' => 2.3, 'str_bonus' => 1.0, 'def_bonus' => 1.0, 'spd_bonus' => 1.5, 'dex_bonus' => 2.5],
            15 => ['energy_cost' => 140, 'multiplier' => 2.4, 'str_bonus' => 3.0, 'def_bonus' => 1.0, 'spd_bonus' => 1.0, 'dex_bonus' => 1.0],
            16 => ['energy_cost' => 160, 'multiplier' => 2.5, 'str_bonus' => 1.0, 'def_bonus' => 3.0, 'spd_bonus' => 1.0, 'dex_bonus' => 1.0],
            17 => ['energy_cost' => 180, 'multiplier' => 2.6, 'str_bonus' => 1.0, 'def_bonus' => 1.0, 'spd_bonus' => 3.0, 'dex_bonus' => 1.0],
            18 => ['energy_cost' => 200, 'multiplier' => 2.7, 'str_bonus' => 1.0, 'def_bonus' => 1.0, 'spd_bonus' => 1.0, 'dex_bonus' => 3.0],
            19 => ['energy_cost' => 250, 'multiplier' => 2.8, 'str_bonus' => 3.5, 'def_bonus' => 1.5, 'spd_bonus' => 1.0, 'dex_bonus' => 1.0],
            20 => ['energy_cost' => 300, 'multiplier' => 2.9, 'str_bonus' => 1.5, 'def_bonus' => 3.5, 'spd_bonus' => 1.0, 'dex_bonus' => 1.0],
            21 => ['energy_cost' => 350, 'multiplier' => 3.0, 'str_bonus' => 4.0, 'def_bonus' => 1.0, 'spd_bonus' => 2.0, 'dex_bonus' => 1.0],
            22 => ['energy_cost' => 400, 'multiplier' => 3.2, 'str_bonus' => 2.0, 'def_bonus' => 4.0, 'spd_bonus' => 1.0, 'dex_bonus' => 1.0],
            23 => ['energy_cost' => 450, 'multiplier' => 3.4, 'str_bonus' => 1.0, 'def_bonus' => 2.0, 'spd_bonus' => 4.0, 'dex_bonus' => 1.0],
            24 => ['energy_cost' => 500, 'multiplier' => 3.6, 'str_bonus' => 1.0, 'def_bonus' => 1.0, 'spd_bonus' => 2.0, 'dex_bonus' => 4.0],
            25 => ['energy_cost' => 600, 'multiplier' => 3.8, 'str_bonus' => 4.5, 'def_bonus' => 2.5, 'spd_bonus' => 1.0, 'dex_bonus' => 1.0],
        ];
        
        return $gymData[$gymId] ?? ['energy_cost' => 100, 'multiplier' => 1.0, 'str_bonus' => 1.0, 'def_bonus' => 1.0, 'spd_bonus' => 1.0, 'dex_bonus' => 1.0];
    }
}
