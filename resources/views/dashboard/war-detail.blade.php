@extends('layouts.app')

@section('title', 'War Details - TornOps')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="/wars" class="text-gray-400 hover:text-white text-sm mb-2 inline-block">← Terug naar Wars</a>
            <h1 class="text-3xl font-bold">{{ $war->opponent_faction_name ?? 'Onbekende Faction' }}</h1>
            <span class="text-gray-400">War #{{ $war->war_id }}</span>
        </div>
        <div>
            @if($war->status === 'won')
                <span class="px-4 py-2 rounded bg-green-900 text-green-400 font-semibold">Gewonnen</span>
            @elseif($war->status === 'lost')
                <span class="px-4 py-2 rounded bg-red-900 text-red-400 font-semibold">Verloren</span>
            @else
                <span class="px-4 py-2 rounded bg-yellow-900 text-yellow-400 font-semibold">{{ ucfirst($war->status) }}</span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 md:col-span-2">
            <h3 class="text-gray-400 text-sm uppercase tracking-wide mb-4">Eindscore</h3>
            <div class="text-center">
                <div class="flex items-center justify-center space-x-8">
                    <div>
                        <p class="text-5xl font-bold text-green-400">{{ $war->score_ours ?? 0 }}</p>
                        <p class="text-gray-400 mt-2">Onze Faction</p>
                    </div>
                    <div class="text-3xl text-gray-500">-</div>
                    <div>
                        <p class="text-5xl font-bold text-red-400">{{ $war->score_them ?? 0 }}</p>
                        <p class="text-gray-400 mt-2">{{ $war->opponent_faction_name ?? 'Tegenstander' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-gray-400 text-sm uppercase tracking-wide mb-2">Start Datum</h3>
            <p class="text-xl">{{ $war->start_date ? $war->start_date->format('d M Y') : 'Onbekend' }}</p>
        </div>

        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-gray-400 text-sm uppercase tracking-wide mb-2">Eind Datum</h3>
            <p class="text-xl">{{ $war->end_date ? $war->end_date->format('d M Y') : 'Onbekend' }}</p>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg border border-gray-700">
        <div class="p-4 border-b border-gray-700">
            <h2 class="text-xl font-semibold">War Attacks</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-gray-400 bg-gray-700/50">
                        <th class="p-4">Attacker</th>
                        <th class="p-4">Defender</th>
                        <th class="p-4">Result</th>
                        <th class="p-4">Score</th>
                        <th class="p-4">Datum</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($war->attacks as $attack)
                    <tr class="hover:bg-gray-700/30">
                        <td class="p-4">{{ $attack->attacker_name ?? 'Onbekend' }}</td>
                        <td class="p-4">{{ $attack->defender_name ?? 'Onbekend' }}</td>
                        <td class="p-4">
                            @if($attack->result === 'win')
                                <span class="px-2 py-1 rounded text-xs bg-green-900 text-green-400">Win</span>
                            @elseif($attack->result === 'lose')
                                <span class="px-2 py-1 rounded text-xs bg-red-900 text-red-400">Lose</span>
                            @else
                                <span class="px-2 py-1 rounded text-xs bg-gray-700 text-gray-400">{{ $attack->result ?? '-' }}</span>
                            @endif
                        </td>
                        <td class="p-4 font-mono">{{ $attack->score_change ?? 0 }}</td>
                        <td class="p-4 text-gray-400 text-sm">
                            {{ $attack->created_at ? $attack->created_at->format('d M Y H:i') : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-400">
                            Geen attacks gevonden voor deze war.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
