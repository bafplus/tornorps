<?php

namespace App\Http\Controllers;

use App\Models\FactionSettings;
use App\Models\OverdoseEvent;
use App\Models\FactionMember;
use Illuminate\Support\Facades\View;

class OverdoseController extends Controller
{
    public function index()
    {
        $factionId = FactionSettings::value('faction_id');
        
        $events = OverdoseEvent::where('faction_id', $factionId)
            ->orderBy('detected_at', 'desc')
            ->paginate(50);
        
        $playerIds = $events->pluck('player_id')->unique()->toArray();
        $members = FactionMember::where('faction_id', $factionId)
            ->whereIn('player_id', $playerIds)
            ->get()
            ->keyBy('player_id');
        
        foreach ($events as $event) {
            $event->member_name = $members->get($event->player_id)?->name ?? "Unknown ({$event->player_id})";
        }
        
        return view('overdose.index', compact('events'));
    }
}
