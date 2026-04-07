<?php

namespace App\Http\Controllers;

use App\Models\FactionSettings;
use App\Models\User;
use App\Models\DataRefreshLog;
use App\Models\ScheduledJob;
use App\Services\TornApiService;
use App\Services\WarService;
use App\Services\DiscordBotService;
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
        $warActive = WarService::hasActiveWar();
        
        // Get last run times for each API endpoint
        $apiSchedule = $this->getApiSchedule($warActive);
        
        return view('admin.index', compact('settings', 'users', 'apiSchedule', 'warActive'));
    }

    private function getApiSchedule(bool $warActive): array
    {
        $nonEssentialDuringWar = ['faction_sync', 'faction_members', 'stocks'];
        
        $schedule = [
            'faction_sync' => [
                'name' => 'torn:sync-faction',
                'schedule' => 'Every 10 min (:00)',
                'description' => 'Runs sync-members and sync-wars together',
                'api_calls' => '6-25 calls',
                'essential' => false,
            ],
            'faction_members' => [
                'name' => 'torn:sync-members',
                'schedule' => 'Every 10 min (via sync-faction)',
                'description' => 'Syncs faction members, FF scores, and stats',
                'api_calls' => '1-5 calls',
                'essential' => false,
            ],
            'ranked_wars' => [
                'name' => 'torn:sync-wars',
                'schedule' => 'Every 10 min (via sync-faction)',
                'description' => 'Syncs ranked wars, members, and war data',
                'api_calls' => '5-20 calls',
                'essential' => false,
            ],
            'active_wars' => [
                'name' => 'torn:sync-active',
                'schedule' => 'Every 1 min (war mode)',
                'description' => 'War updates with cached online status',
                'api_calls' => '3-10 calls',
                'essential' => true,
            ],
            'war_attacks' => [
                'name' => 'torn:sync-attacks',
                'schedule' => 'Every 1 min (war mode)',
                'description' => 'Syncs war attack details with FF and results',
                'api_calls' => '10-50 calls',
                'essential' => true,
            ],
            'stocks' => [
                'name' => 'torn:sync-stocks',
                'schedule' => 'Daily (00:15)',
                'description' => 'Syncs stock prices and saves to history',
                'api_calls' => '1 call',
                'essential' => false,
            ],
        ];

        // Get last run times
        foreach ($schedule as $key => &$item) {
            $lastRun = DataRefreshLog::where('data_type', $key)
                ->where('status', 'completed')
                ->latest('completed_at')
                ->first();
            
            $item['last_run'] = $lastRun?->completed_at?->diffForHumans() ?? 'Never';
            $item['last_run_at'] = $lastRun?->completed_at;
            $item['disabled'] = $warActive && !$item['essential'];
        }

        // Sort by next run (last run + 5 minutes)
        uasort($schedule, function ($a, $b) {
            $aTime = $a['last_run_at'] ? $a['last_run_at']->addMinutes(5)->timestamp : 0;
            $bTime = $b['last_run_at'] ? $b['last_run_at']->addMinutes(5)->timestamp : 0;
            return $aTime - $bTime;
        });

        return $schedule;
    }

    public function updateFactionSettings(Request $request)
    {
        $request->validate([
            'faction_id' => ['required', 'integer'],
            'torn_api_key' => ['required', 'string', 'max:100'],
            'ffscouter_api_key' => ['nullable', 'string', 'max:100'],
            'auto_sync_enabled' => ['boolean'],
            'base_domain' => ['nullable', 'string', 'max:255'],
            'discord_enabled' => ['boolean'],
            'discord_bot_token' => ['nullable', 'string', 'max:100'],
            'discord_server_id' => ['nullable', 'integer'],
            'discord_channel_id' => ['nullable', 'integer'],
        ]);

        $settings = FactionSettings::first();
        $settings->update([
            'faction_id' => $request->faction_id,
            'torn_api_key' => $request->torn_api_key,
            'ffscouter_api_key' => $request->ffscouter_api_key,
            'auto_sync_enabled' => $request->boolean('auto_sync_enabled'),
            'base_domain' => $request->input('base_domain') ?: null,
            'discord_enabled' => $request->boolean('discord_enabled'),
            'discord_bot_token' => $request->input('discord_bot_token') ?: null,
            'discord_server_id' => $request->input('discord_server_id') ?: null,
            'discord_channel_id' => $request->input('discord_channel_id') ?: null,
        ]);

        if ($request->boolean('discord_enabled') && $request->input('discord_bot_token')) {
            try {
                $discord = app(DiscordBotService::class);
                $discord->restart();
            } catch (\Exception $e) {
                return back()->with('error', 'Failed to restart Discord bot: ' . $e->getMessage());
            }
        }

        return back()->with('status', 'Faction settings updated successfully.');
    }

    public function restartDiscordBot(Request $request)
    {
        try {
            $discord = app(DiscordBotService::class);
            $discord->restart();
            return back()->with('status', 'Discord bot restarted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to restart Discord bot: ' . $e->getMessage());
        }
    }

    public function createUser(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:users'],
            'torn_player_id' => ['required', 'integer', 'unique:users'],
            'is_admin' => ['boolean'],
        ]);

        $settings = FactionSettings::first();
        
        if (!$settings || !$settings->torn_api_key) {
            return back()->with('error', 'Torn API key not configured.');
        }

        $tornApi = new TornApiService();
        $playerData = $tornApi->getPlayer($request->torn_player_id, 'profile');

        if (!$playerData || !isset($playerData['faction'])) {
            return back()->with('error', 'Player not found or has no faction.');
        }

        if (!isset($playerData['faction']['faction_id']) || 
            $playerData['faction']['faction_id'] != $settings->faction_id) {
            return back()->with('error', 'Player is not a member of the faction.');
        }

        $user = User::create([
            'name' => $request->name,
            'torn_player_id' => $request->torn_player_id,
            'is_admin' => $request->boolean('is_admin'),
            'invited_by' => auth()->id(),
            'status' => User::STATUS_INVITED,
        ]);

        $token = $user->regenerateInvitationToken();

        $inviteUrl = null;
        $warning = null;
        
        if ($settings->base_domain) {
            $inviteUrl = rtrim($settings->base_domain, '/') . '/invite/' . $token;
        } else {
            $warning = 'Base Domain not configured. Full invite link not generated. Configure in Settings to enable automatic link generation.';
        }

        return back()->with([
            'status' => 'User created successfully.',
            'invite_url' => $inviteUrl,
            'invite_token' => $token,
            'invited_user_name' => $user->name,
            'base_domain_warning' => $warning,
        ]);
    }

    public function regenerateInvite(User $user)
    {
        $settings = FactionSettings::first();
        $token = $user->regenerateInvitationToken();

        $inviteUrl = null;
        if ($settings->base_domain) {
            $inviteUrl = rtrim($settings->base_domain, '/') . '/invite/' . $token;
        }

        return back()->with([
            'status' => 'Invitation regenerated.',
            'invite_url' => $inviteUrl,
            'invited_user_name' => $user->name,
        ]);
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
            
            $gitPullCmd = 'mkdir -p /tmp/git-home && HOME=/tmp/git-home GIT_CONFIG_COUNT=1 GIT_CONFIG_KEY_0="safe.directory" GIT_CONFIG_VALUE_0="/var/www/html" /usr/bin/git -C /var/www/html remote set-url origin https://github.com/bafplus/tornops.git 2>&1; HOME=/tmp/git-home GIT_CONFIG_COUNT=1 GIT_CONFIG_KEY_0="safe.directory" GIT_CONFIG_VALUE_0="/var/www/html" /usr/bin/git -C /var/www/html pull 2>&1';
            exec('sudo -n /bin/sh -c "chown -R www-data:www-data /var/www/html/.git"', $chownOut, $chownRet);
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

    public function scheduledJobs()
    {
        $jobs = ScheduledJob::orderBy('command')->get();
        return view('admin.scheduled-jobs', compact('jobs'));
    }

    public function updateScheduledJob(Request $request, $id)
    {
        $job = ScheduledJob::findOrFail($id);
        
        $cronHour = $request->input('cron_hour') ?: '*';
        $cronMin = $request->input('cron_min') ?: '*';
        $cronExpression = "{$cronMin} {$cronHour} * * *";
        
        $warHour = $request->input('war_hour') ?: '*';
        $warMin = $request->input('war_min') ?: '*';
        $warCron = "{$warMin} {$warHour} * * *";
        
        $job->update([
            'enabled' => $request->boolean('enabled'),
            'cron_expression' => $cronExpression,
            'war_mode_only' => $request->boolean('war_mode_only'),
            'war_enabled' => $request->boolean('war_enabled'),
            'war_cron' => $warCron,
        ]);

        return back()->with('status', 'Job updated successfully.');
    }

    public function seedScheduledJobs()
    {
        $definitions = [
            'torn:sync-faction' => [
                'description' => 'Sync faction data (members, stats, info)',
                'war_mode_only' => false,
                'default_cron' => '*/5 * * * *',
            ],
            'torn:sync-wars' => [
                'description' => 'Sync ranked wars list',
                'war_mode_only' => true,
                'default_cron' => '*/10 * * * *',
                'war_cron' => '*/5 * * * *',
            ],
            'torn:sync-active' => [
                'description' => 'Sync active war details and scores',
                'war_mode_only' => true,
                'default_cron' => '*/10 * * * *',
                'war_cron' => '*/1 * * * *',
            ],
            'torn:sync-attacks' => [
                'description' => 'Sync war attacks data',
                'war_mode_only' => true,
                'default_cron' => '*/10 * * * *',
                'war_cron' => '*/1 * * * *',
            ],
            'torn:sync-chains' => [
                'description' => 'Sync war chain data',
                'war_mode_only' => true,
                'default_cron' => '*/10 * * * *',
                'war_cron' => '*/1 * * * *',
            ],
            'torn:check-faction-membership' => [
                'description' => 'Check faction membership and sync new members',
                'war_mode_only' => false,
                'default_cron' => '0 * * * *',
            ],
            'torn:sync-stocks' => [
                'description' => 'Sync market stocks data',
                'war_mode_only' => false,
                'default_cron' => '0 0 * * *',
            ],
            'torn:sync-items' => [
                'description' => 'Sync item market data',
                'war_mode_only' => false,
                'default_cron' => '0 0 * * *',
            ],
        ];

        $created = 0;
        $updated = 0;

        foreach ($definitions as $command => $config) {
            $exists = ScheduledJob::where('command', $command)->exists();

            $data = [
                'description' => $config['description'],
                'enabled' => true,
                'cron_expression' => $config['default_cron'],
                'war_mode_only' => $config['war_mode_only'],
                'war_enabled' => true,
                'war_cron' => $config['war_cron'] ?? null,
            ];

            if ($exists) {
                ScheduledJob::where('command', $command)->update($data);
                $updated++;
            } else {
                ScheduledJob::create(array_merge(['command' => $command], $data));
                $created++;
            }
        }

        return back()->with('status', "Seeded: {$created} created, {$updated} updated.");
    }
}