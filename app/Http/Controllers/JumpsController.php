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

        // Extract battle stats
        $battleStats = $stats['battlestats'] ?? [];
        $strength = $battleStats['strength'] ?? 0;
        $defense = $battleStats['defense'] ?? 0;
        $speed = $battleStats['speed'] ?? 0;
        $dexterity = $battleStats['dexterity'] ?? 0;
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
