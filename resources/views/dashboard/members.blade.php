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
                    <tr class="text-left text-gray-400 bg-gray-700/50 text-sm">
                        <th class="p-3">Name</th>
                        <th class="p-3">Level</th>
                        <th class="p-3 text-right">FF</th>
                        <th class="p-3 text-right">Stats</th>
                        <th class="p-3">Position</th>
                        <th class="p-3 text-right">Days</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($members as $member)
                    @php
                        $data = $member->data ?? [];
                        $statusData = $data['status'] ?? [];
                        $until = $statusData['until'] ?? 0;
                        $remaining = $until > 0 ? max(0, $until - time()) : 0;
                    @endphp
                    <tr class="hover:bg-gray-700/30">
                        <td class="p-3">
                            <span class="inline-block w-2 h-2 rounded-full mr-2 @if($member->online_status === 'Online') bg-green-500 @elseif($member->online_status === 'Idle') bg-yellow-500 @else bg-gray-500 @endif"></span>
                            <a href="https://www.torn.com/profiles?XID={{ $member->player_id }}" target="_blank" class="text-blue-400 hover:text-blue-300">{{ $member->name ?? 'Unknown' }}</a>
                            <span class="text-gray-500 text-xs ml-1">#{{ $member->player_id }}</span>
                        </td>
                        <td class="p-3">{{ $member->level }}</td>
                        <td class="p-3 text-right font-mono text-green-400">{{ $member->ff_score ?? '-' }}</td>
                        <td class="p-3 text-right font-mono text-gray-400 text-sm">{{ $member->estimated_stats ?? '-' }}</td>
                        <td class="p-3 text-gray-300">{{ $member->position ?? $member->rank ?? '-' }}</td>
                        <td class="p-3 text-right text-gray-400">{{ $member->days_in_faction ?? '-' }}</td>
                        <td class="p-3">
                            @if($member->status_color === 'red')
                                @php
                                    $statusDesc = $statusData['description'] ?? $member->status_description ?? 'Hospital';
                                    if (stripos($statusDesc, 'In hospital for') === 0) {
                                        $statusDesc = 'In Hospital';
                                    }
                                @endphp
                                @if($until > 0 && $remaining > 0)
                                    @php
                                        $h = floor($remaining / 3600);
                                        $m = floor(($remaining % 3600) / 60);
                                        $timeStr = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-red-900/50 text-red-400 text-xs font-medium hospital-timer" data-until="{{ $until }}">
                                        <span class="hospital-time">{{ $statusDesc }} ({{ $timeStr }})</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-red-900/50 text-red-400 text-xs font-medium">{{ $statusDesc }}</span>
                                @endif
                            @elseif($member->status_color === 'blue')
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-blue-900/50 text-blue-400 text-xs font-medium">
                                    <span>{{ $member->status_description ?? 'Traveling' }}</span>
                                </span>
                            @elseif($member->status_color === 'green')
                                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-green-900/50 text-green-400 text-xs font-medium">{{ $member->status_description ?? 'Okay' }}</span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-gray-700/50 text-gray-400 text-xs font-medium">{{ $member->status_description ?? 'Offline' }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-400">
                            No members found. Sync faction data first.
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
