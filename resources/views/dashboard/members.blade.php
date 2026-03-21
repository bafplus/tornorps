@extends('layouts.app')

@section('title', 'Faction Members - TornOps')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold">Faction Members</h1>
        <span class="text-gray-400">{{ $members->total() }} members</span>
    </div>

    <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-gray-400 bg-gray-700/50">
                        <th class="p-4">Name</th>
                        <th class="p-4">Level</th>
                        <th class="p-4">Rank</th>
                        <th class="p-4">Days in Faction</th>
                        <th class="p-4">Last Synced</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($members as $member)
                    <tr class="hover:bg-gray-700/30">
                        <td class="p-4">
                            <a href="#" class="text-blue-400 hover:text-blue-300">{{ $member->name ?? 'Unknown' }}</a>
                            <span class="text-gray-500 text-sm ml-2">#{{ $member->player_id }}</span>
                        </td>
                        <td class="p-4">{{ $member->level }}</td>
                        <td class="p-4 text-gray-300">{{ $member->rank ?? '-' }}</td>
                        <td class="p-4 text-gray-400">{{ $member->days_in_faction ?? '-' }}</td>
                        <td class="p-4 text-gray-400 text-sm">
                            {{ $member->last_synced_at ? $member->last_synced_at->diffForHumans() : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-400">
                            Geen members gevonden. Synchroniseer eerst de faction data.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($members->hasPages())
        <div class="p-4 border-t border-gray-700">
            {{ $members->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
