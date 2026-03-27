<?php

namespace App\Http\Controllers;

use App\Models\GymStatsHistory;
use App\Models\TrainingProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class GymAssistantController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $latestStats = GymStatsHistory::where('user_id', $user->id)
            ->orderBy('recorded_at', 'desc')
            ->first();
        
        // Auto-fetch if no stats exist
        $fetchError = null;
        if (!$latestStats && $user->torn_api_key && $user->torn_player_id) {
            $fetchError = $this->fetchGymStats($user);
            // Re-fetch after auto-fetch
            $latestStats = GymStatsHistory::where('user_id', $user->id)
                ->orderBy('recorded_at', 'desc')
                ->first();
        }
        
        $history = GymStatsHistory::where('user_id', $user->id)
            ->orderBy('recorded_at', 'desc')
            ->paginate(10);
        
        $chartData = GymStatsHistory::where('user_id', $user->id)
            ->orderBy('recorded_at', 'asc')
            ->get();
        
        $programs = TrainingProgram::orderBy('is_custom')->orderBy('name')->get();
        
        $selectedProgram = null;
        $percentages = null;
        
        if ($user->training_program_id) {
            $selectedProgram = TrainingProgram::find($user->training_program_id);
        }
        
        if ($selectedProgram && $selectedProgram->is_custom && $user->custom_percentages) {
            $percentages = json_decode($user->custom_percentages, true);
        } elseif ($selectedProgram) {
            $percentages = [
                'str' => $selectedProgram->str_percent,
                'def' => $selectedProgram->def_percent,
                'spd' => $selectedProgram->spd_percent,
                'dex' => $selectedProgram->dex_percent,
            ];
        }
        
        $trainRecommendation = null;
        if ($latestStats && $percentages) {
            $selectedGym = $request->input('gym_id', $latestStats->gym_id);
            $trainRecommendation = $this->calculateTrainRecommendation($latestStats, $percentages, $selectedGym);
        }
        
        $gyms = collect(range(1, 32))->map(function($id) {
            return ['id' => $id, 'name' => $this->getGymName($id)];
        });
        
         return view('gym-assistant.index', compact('latestStats', 'history', 'chartData', 'programs', 'selectedProgram', 'percentages', 'trainRecommendation', 'gyms', 'fetchError'));
    }

    private function fetchGymStats($user): ?string
    {
        if (!$user->torn_api_key || !$user->torn_player_id) {
            return null;
        }

        try {
            $response = Http::timeout(10)->get('https://api.torn.com/user/' . $user->torn_player_id, [
                'key' => $user->torn_api_key,
                'selections' => 'gym,battlestats'
            ]);

            if ($response->failed()) {
                return 'Failed to fetch gym stats from API';
            }

            $data = $response->json();

            if (isset($data['error'])) {
                return 'API Error: ' . $data['error'];
            }

            $strength = $data['strength'] ?? 0;
            $defense = $data['defense'] ?? 0;
            $speed = $data['speed'] ?? 0;
            $dexterity = $data['dexterity'] ?? 0;
            $gymId = $data['active_gym'] ?? null;
            $gymName = $this->getGymName($gymId);

            GymStatsHistory::create([
                'user_id' => $user->id,
                'strength' => $strength,
                'defense' => $defense,
                'speed' => $speed,
                'dexterity' => $dexterity,
                'gym_name' => $gymName,
                'gym_id' => $gymId,
                'recorded_at' => now(),
            ]);

            return null;
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->torn_api_key) {
            return back()->with('error', 'No API key found. Please add your Torn API key in settings.');
        }
        
        try {
            $response = Http::timeout(10)->get('https://api.torn.com/user/' . $user->torn_player_id, [
                'key' => $user->torn_api_key,
                'selections' => 'gym,battlestats'
            ]);
            
            if ($response->failed()) {
                return back()->with('error', 'Failed to fetch data from Torn API');
            }
            
            $data = $response->json();
            
            if (isset($data['error'])) {
                return back()->with('error', 'API Error: ' . $data['error']);
            }
            
            $strength = $data['strength'] ?? 0;
            $defense = $data['defense'] ?? 0;
            $speed = $data['speed'] ?? 0;
            $dexterity = $data['dexterity'] ?? 0;
            
            $gymId = $data['active_gym'] ?? null;
            $gymName = $this->getGymName($gymId);
            
            $latest = GymStatsHistory::where('user_id', $user->id)
                ->orderBy('recorded_at', 'desc')
                ->first();
            
            if ($latest && 
                $latest->strength == $strength && 
                $latest->defense == $defense && 
                $latest->speed == $speed && 
                $latest->dexterity == $dexterity) {
                return back()->with('success', 'Stats unchanged - no new record created.');
            }
            
            GymStatsHistory::create([
                'user_id' => $user->id,
                'strength' => $strength,
                'defense' => $defense,
                'speed' => $speed,
                'dexterity' => $dexterity,
                'gym_name' => $gymName,
                'gym_id' => $gymId,
                'recorded_at' => now(),
            ]);
            
            return back()->with('success', 'Gym stats updated successfully!');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
    
    public function selectProgram(Request $request)
    {
        $user = Auth::user();
        
        $programId = $request->input('program_id');
        $customStr = $request->input('custom_str', 25);
        $customDef = $request->input('custom_def', 25);
        $customSpd = $request->input('custom_spd', 25);
        $customDex = $request->input('custom_dex', 25);
        
        $program = TrainingProgram::find($programId);
        
        if (!$program) {
            return back()->with('error', 'Program not found.');
        }
        
        $user->training_program_id = $programId;
        
        if ($program->is_custom) {
            $user->custom_percentages = json_encode([
                'str' => (int) $customStr,
                'def' => (int) $customDef,
                'spd' => (int) $customSpd,
                'dex' => (int) $customDex,
            ]);
        } else {
            $user->custom_percentages = null;
        }
        
        $user->save();
        
        return back()->with('success', 'Training program updated!');
    }

    private function calculateTrainRecommendation($latestStats, $percentages, $gymId = null)
    {
        $gymId = $gymId ?? $latestStats->gym_id;
        
        if (!$gymId) {
            return null;
        }
        
        $gymGains = $this->getGymGains($gymId);
        
        if (!$gymGains) {
            return null;
        }
        
        $currentStr = $latestStats->strength;
        $currentDef = $latestStats->defense;
        $currentSpd = $latestStats->speed;
        $currentDex = $latestStats->dexterity;
        $currentTotal = $currentStr + $currentDef + $currentSpd + $currentDex;
        
        if ($currentTotal == 0) {
            return null;
        }
        
        $strPct = $percentages['str'] / 100;
        $defPct = $percentages['def'] / 100;
        $spdPct = $percentages['spd'] / 100;
        $dexPct = $percentages['dex'] / 100;
        
        $targetTotal = $currentTotal;
        
        $targetStr = (int) ($targetTotal * $strPct);
        $targetDef = (int) ($targetTotal * $defPct);
        $targetSpd = (int) ($targetTotal * $spdPct);
        $targetDex = (int) ($targetTotal * $dexPct);
        
        $gainsStr = $gymGains['strength'];
        $gainsDef = $gymGains['defense'];
        $gainsSpd = $gymGains['speed'];
        $gainsDex = $gymGains['dexterity'];
        
        $needsStr = max(0, $targetStr - $currentStr);
        $needsDef = max(0, $targetDef - $currentDef);
        $needsSpd = max(0, $targetSpd - $currentSpd);
        $needsDex = max(0, $targetDex - $currentDex);
        
        $trainsStr = $gainsStr > 0 ? ceil($needsStr / $gainsStr) : 0;
        $trainsDef = $gainsDef > 0 ? ceil($needsDef / $gainsDef) : 0;
        $trainsSpd = $gainsSpd > 0 ? ceil($needsSpd / $gainsSpd) : 0;
        $trainsDex = $gainsDex > 0 ? ceil($needsDex / $gainsDex) : 0;
        
        $totalTrains = $trainsStr + $trainsDef + $trainsSpd + $trainsDex;
        
        return [
            'gym_id' => $gymId,
            'gym_name' => $this->getGymName($gymId),
            'gym_gains' => $gymGains,
            'current' => [
                'str' => $currentStr,
                'def' => $currentDef,
                'spd' => $currentSpd,
                'dex' => $currentDex,
                'total' => $currentTotal,
            ],
            'target' => [
                'str' => $targetStr,
                'def' => $targetDef,
                'spd' => $targetSpd,
                'dex' => $targetDex,
            ],
            'needed' => [
                'str' => $needsStr,
                'def' => $needsDef,
                'spd' => $needsSpd,
                'dex' => $needsDex,
            ],
            'trains' => [
                'str' => $trainsStr,
                'def' => $trainsDef,
                'spd' => $trainsSpd,
                'dex' => $trainsDex,
                'total' => $totalTrains,
            ],
        ];
    }

    private function getGymGains(int $gymId): ?array
    {
        $gymGains = [
            1 => ['strength' => 2, 'defense' => 2, 'speed' => 2, 'dexterity' => 2],
            2 => ['strength' => 2, 'defense' => 2, 'speed' => 2, 'dexterity' => 2],
            3 => ['strength' => 2, 'defense' => 1, 'speed' => 3, 'dexterity' => 2],
            4 => ['strength' => 1, 'defense' => 2, 'speed' => 3, 'dexterity' => 2],
            5 => ['strength' => 2, 'defense' => 3, 'speed' => 1, 'dexterity' => 2],
            6 => ['strength' => 2, 'defense' => 2, 'speed' => 2, 'dexterity' => 2],
            7 => ['strength' => 3, 'defense' => 2, 'speed' => 1, 'dexterity' => 2],
            8 => ['strength' => 2, 'defense' => 2, 'speed' => 2, 'dexterity' => 2],
            9 => ['strength' => 3, 'defense' => 1, 'speed' => 2, 'dexterity' => 2],
            10 => ['strength' => 2, 'defense' => 2, 'speed' => 2, 'dexterity' => 2],
            11 => ['strength' => 3, 'defense' => 2, 'speed' => 1, 'dexterity' => 2],
            12 => ['strength' => 2, 'defense' => 2, 'speed' => 2, 'dexterity' => 2],
            13 => ['strength' => 1, 'defense' => 2, 'speed' => 3, 'dexterity' => 2],
            14 => ['strength' => 1, 'defense' => 2, 'speed' => 3, 'dexterity' => 2],
            15 => ['strength' => 2, 'defense' => 2, 'speed' => 2, 'dexterity' => 2],
            16 => ['strength' => 2, 'defense' => 2, 'speed' => 2, 'dexterity' => 2],
            17 => ['strength' => 2, 'defense' => 3, 'speed' => 1, 'dexterity' => 2],
            18 => ['strength' => 3, 'defense' => 1, 'speed' => 2, 'dexterity' => 2],
            19 => ['strength' => 3, 'defense' => 2, 'speed' => 1, 'dexterity' => 2],
            20 => ['strength' => 2, 'defense' => 2, 'speed' => 2, 'dexterity' => 2],
            21 => ['strength' => 3, 'defense' => 2, 'speed' => 1, 'dexterity' => 2],
            22 => ['strength' => 2, 'defense' => 2, 'speed' => 2, 'dexterity' => 2],
            23 => ['strength' => 2, 'defense' => 2, 'speed' => 2, 'dexterity' => 2],
            24 => ['strength' => 2, 'defense' => 3, 'speed' => 1, 'dexterity' => 2],
            25 => ['strength' => 3, 'defense' => 1, 'speed' => 2, 'dexterity' => 2],
            26 => ['strength' => 2, 'defense' => 2, 'speed' => 2, 'dexterity' => 2],
            27 => ['strength' => 2, 'defense' => 2, 'speed' => 3, 'dexterity' => 1],
            28 => ['strength' => 3, 'defense' => 1, 'speed' => 2, 'dexterity' => 2],
            29 => ['strength' => 1, 'defense' => 2, 'speed' => 3, 'dexterity' => 2],
            30 => ['strength' => 3, 'defense' => 2, 'speed' => 1, 'dexterity' => 2],
            31 => ['strength' => 2, 'defense' => 2, 'speed' => 2, 'dexterity' => 2],
            32 => ['strength' => 3, 'defense' => 2, 'speed' => 2, 'dexterity' => 1],
        ];
        
        return $gymGains[$gymId] ?? null;
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
            26 => 'Frontline Fitness',
            27 => 'Gym 3000',
            28 => 'Mr. Isoyamas',
            29 => 'Total Rebound',
            30 => 'Elites',
            31 => 'The Sports Science Lab',
            32 => 'Fight Club',
        ];
        
        return $gymNames[$gymId] ?? 'Gym #' . $gymId;
    }
}