<?php

namespace App\Http\Controllers;

use App\Models\FactionSettings;
use App\Models\FactionMember;
use App\Models\RankedWar;
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

        return view('dashboard.war-detail', compact('settings', 'war'));
    }
}
