<?php

namespace App\Http\Controllers;

use App\Models\FactionSettings;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Http;

class AdminController extends Controller
{
    public function index()
    {
        $settings = FactionSettings::first();
        $users = User::orderBy('is_admin', 'desc')->orderBy('name')->get();
        
        return view('admin.index', compact('settings', 'users'));
    }

    public function updateFactionSettings(Request $request)
    {
        $request->validate([
            'faction_id' => ['required', 'integer'],
            'torn_api_key' => ['required', 'string', 'max:100'],
            'ffscouter_api_key' => ['nullable', 'string', 'max:100'],
            'auto_sync_enabled' => ['boolean'],
        ]);

        $settings = FactionSettings::first();
        $settings->update([
            'faction_id' => $request->faction_id,
            'torn_api_key' => $request->torn_api_key,
            'ffscouter_api_key' => $request->ffscouter_api_key,
            'auto_sync_enabled' => $request->boolean('auto_sync_enabled'),
        ]);

        return back()->with('status', 'Faction settings updated successfully.');
    }

    public function createUser(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_admin' => ['boolean'],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => $request->boolean('is_admin'),
        ]);

        return back()->with('status', 'User created successfully.');
    }

    public function toggleAdmin(User $user)
    {
        $user->update(['is_admin' => !$user->is_admin]);
        return back()->with('status', 'Admin status toggled.');
    }

    public function deleteUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete yourself.');
        }
        $user->delete();
        return back()->with('status', 'User deleted.');
    }

    public function checkForUpdates()
    {
        $currentVersion = config('tornops.version');
        $currentCommit = config('tornops.commit', '');
        $repo = config('tornops.github_repo');

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'TornOps'])
                ->get("https://api.github.com/repos/{$repo}/releases/latest");
            
            if ($response->successful()) {
                $latestVersion = ltrim($response->json()['tag_name'] ?? 'v1.0.0', 'v');
                $releaseUrl = $response->json()['html_url'] ?? null;

                $updateAvailable = version_compare($latestVersion, $currentVersion, '>');

                return back()->with([
                    'update_check' => true,
                    'current_version' => $currentVersion,
                    'current_commit' => $currentCommit,
                    'latest_version' => $latestVersion,
                    'update_available' => $updateAvailable,
                    'release_url' => $releaseUrl,
                ]);
            }

            if ($response->status() === 404) {
                $commitResponse = Http::timeout(10)
                    ->withHeaders(['User-Agent' => 'TornOps'])
                    ->get("https://api.github.com/repos/{$repo}/commits?per_page=1");
                
                if ($commitResponse->successful()) {
                    $latestCommit = $commitResponse->json()[0]['sha'] ?? null;
                    $commitUrl = $commitResponse->json()[0]['html_url'] ?? null;

                    $updateAvailable = $latestCommit !== null && $latestCommit !== $currentCommit;

                    return back()->with([
                        'update_check' => true,
                        'current_version' => $currentVersion,
                        'current_commit' => $currentCommit,
                        'latest_version' => 'commit: ' . substr($latestCommit, 0, 7),
                        'update_available' => $updateAvailable,
                        'release_url' => $commitUrl,
                        'no_releases' => true,
                    ]);
                }
            }

            $status = $response->status();
            $body = $response->body();
            return back()->with('error', "GitHub API error (HTTP {$status}): {$body}");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to connect to GitHub: ' . $e->getMessage());
        }
    }

    public function upgrade()
    {
        $repo = config('tornops.github_repo');
        
        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'TornOps'])
                ->get("https://api.github.com/repos/{$repo}/commits?per_page=1");
            
            if (!$response->successful()) {
                return back()->with('error', 'Could not fetch latest commit from GitHub.');
            }

            $latestCommit = $response->json()[0]['sha'] ?? null;
            $latestCommitShort = $latestCommit ? substr($latestCommit, 0, 7) : null;
            
            if (!$latestCommit) {
                return back()->with('error', 'Could not determine latest commit.');
            }

            $currentCommit = config('tornops.commit');
            
            if ($currentCommit === $latestCommit) {
                return back()->with('status', 'Already up to date.');
            }

            $output = [];
            $returnCode = 0;
            
            $gitPullCmd = '/usr/bin/git config --global --add safe.directory /var/www/html 2>&1; /usr/bin/git -C /var/www/html pull 2>&1';
            exec($gitPullCmd, $output, $returnCode);
            
            if ($returnCode === 0) {
                $configPath = base_path('config/tornops.php');
                $configContent = file_get_contents($configPath);
                $newContent = preg_replace(
                    "/'commit' => '[^']*'/",
                    "'commit' => '{$latestCommit}'",
                    $configContent
                );
                file_put_contents($configPath, $newContent);

                exec('composer install --no-dev --quiet 2>&1', $composerOutput, $composerReturn);
                
                $message = 'Application upgraded to ' . $latestCommitShort;
                if ($composerReturn === 0) {
                    $message .= '. Composer dependencies updated.';
                }
                
                return back()->with('status', $message);
            }
            
            return back()->with('error', 'Git pull failed: ' . implode("\n", $output));
        } catch (\Exception $e) {
            return back()->with('error', 'Upgrade failed: ' . $e->getMessage());
        }
    }
}
