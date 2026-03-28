<?php

namespace App\Http\Controllers;

use App\Services\TornApiService;
use Illuminate\Support\Facades\Auth;

class JumpsController extends Controller
{
    public function index(TornApiService $tornApi)
    {
        $user = Auth::user();
        $apiKey = $user->torn_api_key;

        if (!$apiKey) {
            return view('jumps.index', [
                'error' => 'No API key found. Please add your Torn API key in Settings.',
                'bars' => null,
                'stats' => null,
            ]);
        }

        // Fetch user bars (happy, energy, etc.)
        $bars = $tornApi->getUserBars($apiKey);
        
        // Fetch user battle stats
        $stats = $tornApi->getUserStats($apiKey);

        if (!$bars || !$stats) {
            return view('jumps.index', [
                'error' => 'Failed to fetch user data. Check your API key.',
                'bars' => null,
                'stats' => null,
            ]);
        }

        // Extract battle stats - handle both formats (direct value or object with 'total')
        $battleStats = $stats['battlestats'] ?? [];
        $getValue = function ($stat) {
            if (is_array($stat)) {
                return $stat['total'] ?? $stat['value'] ?? 0;
            }
            return $stat ?? 0;
        };
        
        $strength = $getValue($battleStats['strength'] ?? 0);
        $defense = $getValue($battleStats['defense'] ?? 0);
        $speed = $getValue($battleStats['speed'] ?? 0);
        $dexterity = $getValue($battleStats['dexterity'] ?? 0);
        $totalStats = $strength + $defense + $speed + $dexterity;

        // Extract bars
        $happy = $bars['happy'] ?? [];
        $energy = $bars['energy'] ?? [];
        $currentHappy = $happy['current'] ?? 0;
        $maxHappy = $happy['maximum'] ?? 0;

        return view('jumps.index', [
            'error' => null,
            'bars' => $bars,
            'stats' => $battleStats,
            'current_happy' => $currentHappy,
            'max_happy' => $maxHappy,
            'current_energy' => $energy['current'] ?? 0,
            'max_energy' => $energy['maximum'] ?? 0,
            'strength' => $strength,
            'defense' => $defense,
            'speed' => $speed,
            'dexterity' => $dexterity,
            'total_stats' => $totalStats,
        ]);
    }
}
