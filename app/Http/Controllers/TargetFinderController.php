<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class TargetFinderController extends Controller
{
    private function getDefaultSettings(): array
    {
        return [
            'easy' => [
                'minFF' => 1.5,
                'maxFF' => 2.0,
                'minLevel' => 1,
                'maxLevel' => 100,
            ],
            'good' => [
                'minFF' => 2.5,
                'maxFF' => 3.0,
                'minLevel' => 1,
                'maxLevel' => 100,
            ],
            'inactiveOnly' => true,
            'factionlessOnly' => false,
            'verifyStatus' => false,
        ];
    }

    private function getUserSettings($user): array
    {
        if ($user->ffscout_settings) {
            $settings = is_array($user->ffscout_settings)
                ? $user->ffscout_settings
                : json_decode($user->ffscout_settings, true);

            return array_merge($this->getDefaultSettings(), $settings);
        }

        return $this->getDefaultSettings();
    }

    public function index()
    {
        $user = Auth::user();
        $settings = $this->getUserSettings($user);

        return view('target-finder.index', compact('settings'));
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'easy.minFF' => 'required|numeric|min:1|max:5',
            'easy.maxFF' => 'required|numeric|min:1|max:5',
            'easy.minLevel' => 'required|integer|min:1|max:100',
            'easy.maxLevel' => 'required|integer|min:1|max:100',
            'good.minFF' => 'required|numeric|min:1|max:5',
            'good.maxFF' => 'required|numeric|min:1|max:5',
            'good.minLevel' => 'required|integer|min:1|max:100',
            'good.maxLevel' => 'required|integer|min:1|max:100',
        ]);

        if ($request->input('easy.minFF') > $request->input('easy.maxFF')) {
            return back()->with('error', 'Easy: Min FF cannot exceed Max FF');
        }
        if ($request->input('good.minFF') > $request->input('good.maxFF')) {
            return back()->with('error', 'Good: Min FF cannot exceed Max FF');
        }
        if ($request->input('easy.minLevel') > $request->input('easy.maxLevel')) {
            return back()->with('error', 'Easy: Min Level cannot exceed Max Level');
        }
        if ($request->input('good.minLevel') > $request->input('good.maxLevel')) {
            return back()->with('error', 'Good: Min Level cannot exceed Max Level');
        }

        $user = Auth::user();
        $settings = [
            'easy' => [
                'minFF' => (float) $request->input('easy.minFF'),
                'maxFF' => (float) $request->input('easy.maxFF'),
                'minLevel' => (int) $request->input('easy.minLevel'),
                'maxLevel' => (int) $request->input('easy.maxLevel'),
            ],
            'good' => [
                'minFF' => (float) $request->input('good.minFF'),
                'maxFF' => (float) $request->input('good.maxFF'),
                'minLevel' => (int) $request->input('good.minLevel'),
                'maxLevel' => (int) $request->input('good.maxLevel'),
            ],
            'inactiveOnly' => $request->has('inactiveOnly'),
            'factionlessOnly' => $request->has('factionlessOnly'),
            'verifyStatus' => $request->has('verifyStatus'),
        ];

        $user->ffscout_settings = json_encode($settings);
        $user->save();

        return back()->with('success', 'Settings saved successfully!');
    }

    public function getTarget(Request $request, string $type)
    {
        $user = Auth::user();

        if (!$user->torn_api_key) {
            return response()->json([
                'success' => false,
                'error' => 'No API key found. Please add your Torn API key in settings.',
            ], 400);
        }

        if (!in_array($type, ['easy', 'good'])) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid target type',
            ], 400);
        }

        $settings = $this->getUserSettings($user);
        $targetSettings = $settings[$type];

        $params = [
            'key' => $user->torn_api_key,
            'minff' => $targetSettings['minFF'],
            'maxff' => $targetSettings['maxFF'],
            'minlevel' => $targetSettings['minLevel'],
            'maxlevel' => $targetSettings['maxLevel'],
            'inactiveonly' => $settings['inactiveOnly'] ? 1 : 0,
            'factionless' => $settings['factionlessOnly'] ? 1 : 0,
            'limit' => 50,
        ];

        try {
            $response = Http::timeout(15)->get('https://ffscouter.com/api/v1/get-targets', $params);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to connect to FFScout API',
                ], 500);
            }

            $data = $response->json();

            if (isset($data['error'])) {
                return response()->json([
                    'success' => false,
                    'error' => $data['error'],
                ], 400);
            }

            $targets = $data['targets'] ?? [];

            if (empty($targets)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No targets found with current filters',
                ], 404);
            }

            $target = $this->findValidTarget($targets, $user->torn_api_key, $settings['verifyStatus']);

            if (!$target) {
                return response()->json([
                    'success' => false,
                    'error' => 'No available targets found',
                ], 404);
            }

            $attackUrl = 'https://www.torn.com/loader.php?sid=attack&user2ID=' . $target['player_id'];

            return response()->json([
                'success' => true,
                'target' => $target,
                'attackUrl' => $attackUrl,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function findValidTarget(array $targets, string $apiKey, bool $verifyStatus): ?array
    {
        if (!$verifyStatus) {
            return $targets[array_rand($targets)];
        }

        shuffle($targets);

        foreach ($targets as $target) {
            try {
                $response = Http::timeout(5)->get("https://api.torn.com/v2/user/{$target['player_id']}/basic", [
                    'key' => $apiKey,
                    'striptags' => 'true',
                ]);

                if ($response->failed()) {
                    continue;
                }

                $data = $response->json();

                if (isset($data['error'])) {
                    continue;
                }

                $state = $data['profile']['status']['state'] ?? null;

                if ($state === 'Okay') {
                    return $target;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    public function getTargetCount(Request $request, string $type)
    {
        $user = Auth::user();

        if (!$user->torn_api_key) {
            return response()->json(['success' => false, 'count' => 0]);
        }

        if (!in_array($type, ['easy', 'good'])) {
            return response()->json(['success' => false, 'count' => 0]);
        }

        $settings = $this->getUserSettings($user);
        $targetSettings = $settings[$type];

        $params = [
            'key' => $user->torn_api_key,
            'minff' => $targetSettings['minFF'],
            'maxff' => $targetSettings['maxFF'],
            'minlevel' => $targetSettings['minLevel'],
            'maxlevel' => $targetSettings['maxLevel'],
            'inactiveonly' => $settings['inactiveOnly'] ? 1 : 0,
            'factionless' => $settings['factionlessOnly'] ? 1 : 0,
            'limit' => 50,
        ];

        try {
            $response = Http::timeout(10)->get('https://ffscouter.com/api/v1/get-targets', $params);

            if ($response->failed()) {
                return response()->json(['success' => false, 'count' => 0]);
            }

            $data = $response->json();

            if (isset($data['error'])) {
                return response()->json(['success' => false, 'count' => 0]);
            }

            $count = count($data['targets'] ?? []);
            return response()->json(['success' => true, 'count' => $count]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'count' => 0]);
        }
    }

    public function checkKeyStatus()
    {
        $user = Auth::user();

        if (!$user->torn_api_key) {
            return response()->json([
                'success' => false,
                'hasKey' => false,
                'error' => 'No API key found',
            ]);
        }

        try {
            $response = Http::timeout(10)->get('https://ffscouter.com/api/v1/check-key', [
                'key' => $user->torn_api_key,
            ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'isRegistered' => false,
                    'error' => 'Failed to connect to FFScout API',
                ]);
            }

            $data = $response->json();

            if (isset($data['error'])) {
                return response()->json([
                    'success' => false,
                    'isRegistered' => false,
                    'error' => $data['error'],
                ]);
            }

            return response()->json([
                'success' => true,
                'isRegistered' => $data['is_registered'] ?? false,
                'registeredAt' => $data['registered_at'] ?? null,
                'lastUsed' => $data['last_used'] ?? null,
                'policyVersion' => $data['policy_version'] ?? null,
                'policyUpdateRequired' => $data['policy_update_required'] ?? false,
                'isPremium' => $data['is_premium'] ?? false,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'isRegistered' => false,
                'error' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    public function registerKey(Request $request)
    {
        $request->validate([
            'agree_to_policy' => 'required|boolean',
        ]);

        $user = Auth::user();

        if (!$user->torn_api_key) {
            return response()->json([
                'success' => false,
                'error' => 'No API key found. Please add your Torn API key in settings.',
            ], 400);
        }

        if (!$request->input('agree_to_policy')) {
            return response()->json([
                'success' => false,
                'error' => 'You must agree to the data policy to register.',
            ], 400);
        }

        try {
            $response = Http::timeout(30)->post('https://ffscouter.com/api/v1/register', [
                'key' => $user->torn_api_key,
                'agree_to_data_policy' => true,
                'signup_source' => 'TornOps',
            ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to connect to FFScout API',
                ], 500);
            }

            $data = $response->json();

            if (isset($data['error'])) {
                return response()->json([
                    'success' => false,
                    'error' => $data['error'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'API key registered successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}