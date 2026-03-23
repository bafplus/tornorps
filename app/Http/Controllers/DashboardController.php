<?php

namespace App\Http\Controllers;

use App\Models\FactionSettings;
use App\Models\FactionMember;
use App\Models\RankedWar;
use App\Models\WarAttack;
use App\Models\WarChain;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $settings = FactionSettings::first();
        $totalMembers = FactionMember::where('faction_id', $settings->faction_id ?? 0)->count();
        $activeWars = RankedWar::where('faction_id', $settings->faction_id ?? 0)
            ->whereIn('status', ['pending', 'accepted', 'in progress'])
            ->count();
        $recentWars = RankedWar::where('faction_id', $settings->faction_id ?? 0)
            ->orderBy('start_date', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard.index', compact('settings', 'totalMembers', 'activeWars', 'recentWars'));
    }

    public function members()
    {
        $settings = FactionSettings::first();
        $members = FactionMember::where('faction_id', $settings->faction_id ?? 0)
            ->orderBy('name')
            ->paginate(25);

        return view('dashboard.members', compact('settings', 'members'));
    }

    public function wars()
    {
        $settings = FactionSettings::first();
        $activeWars = RankedWar::where('faction_id', $settings->faction_id ?? 0)
            ->whereIn('status', ['pending', 'accepted', 'in progress'])
            ->orderBy('start_date', 'desc')
            ->get();
        $pastWars = RankedWar::where('faction_id', $settings->faction_id ?? 0)
            ->whereNotIn('status', ['pending', 'accepted', 'in progress'])
            ->orderBy('start_date', 'desc')
            ->limit(20)
            ->get();

        return view('dashboard.wars', compact('settings', 'activeWars', 'pastWars'));
    }

    public function warDetail($warId)
    {
        $settings = FactionSettings::first();
        $war = RankedWar::where('war_id', $warId)
            ->where('faction_id', $settings->faction_id ?? 0)
            ->firstOrFail();
        
        $ourMembers = $war->members()
            ->where('faction_id', $settings->faction_id)
            ->orderBy('war_score', 'desc')
            ->get();
        
        $opponentMembers = $war->members()
            ->where('faction_id', $war->opponent_faction_id)
            ->orderBy('war_score', 'desc')
            ->get();

$ourFactionId = $settings->faction_id;
    $oppFactionId = $war->opponent_faction_id;
    $ourMemberIds = $war->members()->where('faction_id', $ourFactionId)->pluck('player_id');
    $oppMemberIds = $war->members()->where('faction_id', $oppFactionId)->pluck('player_id');

    $attackStats = WarAttack::where('war_id', $warId)
        ->whereIn('attacker_id', $ourMemberIds)
        ->whereIn('defender_id', $oppMemberIds)
        ->selectRaw('attacker_id,
            COUNT(*) as total_attacks,
            SUM(CASE WHEN result = "Attacked" OR result = "Hospitalized" THEN 1 ELSE 0 END) as successful,
            SUM(CASE WHEN result = "Lost" OR result = "Stalemate" THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN result = "Interrupted" THEN 1 ELSE 0 END) as interrupted,
            SUM(respect_gain) as total_score')
        ->groupBy('attacker_id')
        ->get()
        ->keyBy('attacker_id');

    $attacks = WarAttack::where('war_id', $warId)->orderByDesc('timestamp')->paginate(10);

    $retaliationTargets = WarAttack::where('war_id', $warId)
        ->whereIn('defender_id', $ourMemberIds)
        ->whereIn('attacker_id', $oppMemberIds)
        ->whereIn('result', ['Attacked', 'Hospitalized'])
        ->where('timestamp', '>=', now()->subMinutes(5))
        ->orderByDesc('timestamp')
        ->get()
        ->map(function ($attack) {
            $attackTime = $attack->timestamp;
            $windowEnd = $attackTime->copy()->addSeconds(300);
            $retaliationHit = WarAttack::where('war_id', $attack->war_id)
                ->where('attacker_id', $attack->defender_id)
                ->where('defender_id', $attack->attacker_id)
                ->where('timestamp', '>', $attackTime)
                ->where('timestamp', '<=', $windowEnd)
                ->whereIn('result', ['Attacked', 'Hospitalized'])
                ->exists();

            return [
                'target_id' => $attack->attacker_id,
                'target_name' => $attack->attacker_name,
                'attacked_by' => $attack->defender_name,
                'timestamp' => $attackTime,
                'expires_at' => $windowEnd,
                'retaliated' => $retaliationHit,
            ];
        })
        ->filter(fn($r) => !$r['retaliated']);

    $ourChain = WarChain::where('war_id', $warId)->where('faction_id', $ourFactionId)->first();
    $oppChain = WarChain::where('war_id', $warId)->where('faction_id', $oppFactionId)->first();
    $chainStats = [
        'chain_hits' => $ourChain?->chain_hits ?? 0,
        'max_chain' => $ourChain?->max_chain ?? 0,
        'chain_respect' => $ourChain?->chain_respect ?? 0,
        'avg_bonus' => 1.0,
    ];

    $activeChain = null;
    if ($ourChain && $ourChain->expires_at && $ourChain->expires_at > now()) {
        $activeChain = [
            'level' => $ourChain->current_chain,
            'expires_at' => $ourChain->expires_at,
            'chain_hits' => $ourChain->chain_hits ?? 0,
            'max_chain' => $ourChain->max_chain ?? 0,
            'chain_respect' => $ourChain->chain_respect ?? 0,
            'avg_bonus' => 1.0,
            'faction_name' => $settings->faction_name ?? 'Our Faction',
        ];
    }

    $oppActiveChain = null;
    if ($oppChain && $oppChain->expires_at && $oppChain->expires_at > now()) {
        $oppActiveChain = [
            'level' => $oppChain->current_chain,
            'expires_at' => $oppChain->expires_at,
            'chain_hits' => $oppChain->chain_hits ?? 0,
            'max_chain' => $oppChain->max_chain ?? 0,
            'chain_respect' => $oppChain->chain_respect ?? 0,
            'avg_bonus' => 1.0,
            'faction_name' => $war->opponent_faction_name ?? 'Opponent',
        ];
    }

    return view('dashboard.war-detail', compact('settings', 'war', 'ourMembers', 'opponentMembers', 'attackStats', 'attacks', 'retaliationTargets', 'chainStats', 'activeChain', 'oppActiveChain'));
    }

    public function warStats(int $warId)
    {
        $settings = FactionSettings::first();
        
        $attackStats = WarAttack::where('war_id', $warId)
            ->selectRaw('attacker_id, 
                         COUNT(*) as total_attacks,
                         SUM(CASE WHEN result = "Attacked" OR result = "Hospitalized" THEN 1 ELSE 0 END) as successful,
                         SUM(CASE WHEN result = "Lost" OR result = "Stalemate" THEN 1 ELSE 0 END) as failed,
                         SUM(CASE WHEN result = "Interrupted" THEN 1 ELSE 0 END) as interrupted,
                         SUM(respect_gain) as total_score')
            ->groupBy('attacker_id')
            ->get()
            ->keyBy('attacker_id');

$stats = [];
    foreach ($attackStats as $playerId => $stat) {
        $stats[$playerId] = [
            'hits' => (int) $stat->successful,
            'successful' => (int) $stat->successful,
            'failed' => (int) $stat->failed,
            'interrupted' => (int) $stat->interrupted,
            'score' => round($stat->total_score, 2)
        ];
    }

        return response()->json([
            'stats' => $stats,
            'synced_at' => now()->toIso8601String()
        ]);
    }
}
