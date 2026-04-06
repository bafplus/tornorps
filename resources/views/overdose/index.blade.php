@extends('layouts.app')

@section('title', 'Overdose History')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6">Overdose History</h1>

    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Member</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Count</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Detected At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($events as $event)
                <tr class="hover:bg-gray-750">
                    <td class="px-4 py-3">
                        <a href="https://www.torn.com/profiles.php?XID={{ $event->player_id }}" target="_blank" class="text-blue-400 hover:text-blue-300">
                            {{ $event->member_name }}
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-red-400 font-medium">#{{ $event->count_at_time }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-400">
                        {{ $event->detected_at->diffForHumans() }}
                        <span class="text-gray-500 text-xs">({{ $event->detected_at->format('Y-m-d H:i:s') }})</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                        No overdose events recorded yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($events->hasPages())
    <div class="mt-4">
        {{ $events->links() }}
    </div>
    @endif
</div>
@endsection
