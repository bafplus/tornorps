@extends('layouts.app')

@section('title', 'Dashboard - TornOps')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold">Dashboard</h1>
        <span class="text-gray-400">Faction: {{ $settings->faction_id ?? 'N/A' }}</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-gray-400 text-sm uppercase tracking-wide mb-2">Total Members</h3>
            <p class="text-4xl font-bold text-blue-400">{{ $totalMembers }}</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-gray-400 text-sm uppercase tracking-wide mb-2">Active Wars</h3>
            <p class="text-4xl font-bold text-purple-400">{{ $activeWars }}</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-gray-400 text-sm uppercase tracking-wide mb-2">Last Sync</h3>
            <p class="text-lg font-semibold text-green-400">
                {{ $settings->updated_at ? $settings->updated_at->diffForHumans() : 'Never' }}
            </p>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-blue-400">Recent Ranked Wars</h2>
            <a href="/wars" class="text-blue-400 hover:text-blue-300 text-sm">View all →</a>
        </div>
        
        @if($recentWars->isEmpty())
            <p class="text-gray-400">No wars found.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-gray-400 border-b border-gray-700">
                            <th class="pb-3">Opponent</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3">Score</th>
                            <th class="pb-3">Start</th>
                            <th class="pb-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @foreach($recentWars as $war)
                        <tr class="hover:bg-gray-700/50">
                            <td class="py-4">{{ $war->opponent_faction_name ?? 'Unknown' }}</td>
                            <td class="py-4">
                                <span class="px-2 py-1 rounded text-xs 
                                    @if($war->status === 'won') bg-green-900 text-green-400
                                    @elseif($war->status === 'lost') bg-red-900 text-red-400
                                    @else bg-yellow-900 text-yellow-400
                                    @endif">
                                    {{ ucfirst($war->status) }}
                                </span>
                            </td>
                            <td class="py-4 font-mono">
                                {{ $war->score_ours ?? '-' }} - {{ $war->score_them ?? '-' }}
                            </td>
                            <td class="py-4 text-gray-400">
                                {{ $war->start_date ? $war->start_date->format('d M Y') : '-' }}
                            </td>
                            <td class="py-4">
                                <a href="/wars/{{ $war->war_id }}" class="text-blue-400 hover:text-blue-300">Details →</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
