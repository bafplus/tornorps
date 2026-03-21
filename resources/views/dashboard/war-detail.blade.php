@extends('layouts.app')

@section('title', 'War Details - TornOps')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="/wars" class="text-gray-400 hover:text-white text-sm mb-2 inline-block">← Back to Wars</a>
            <h1 class="text-3xl font-bold">{{ $war->opponent_faction_name ?? 'Unknown Faction' }}</h1>
            <span class="text-gray-400">War #{{ $war->war_id }}</span>
        </div>
        <div>
            @if($war->status === 'won')
                <span class="px-4 py-2 rounded bg-green-900 text-green-400 font-semibold">Won</span>
            @elseif($war->status === 'lost')
                <span class="px-4 py-2 rounded bg-red-900 text-red-400 font-semibold">Lost</span>
            @else
                <span class="px-4 py-2 rounded bg-yellow-900 text-yellow-400 font-semibold">{{ ucfirst($war->status) }}</span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-gray-400 text-sm uppercase tracking-wide mb-2">Start Date (UTC)</h3>
            <p class="text-xl font-mono">{{ $war->start_date ? $war->start_date->format('d M Y H:i') : 'Unknown' }}</p>
            <p class="text-2xl font-bold font-mono mt-2" id="timer-display"></p>
            <p class="text-gray-400 text-sm" id="timer-label"></p>
        </div>

        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-gray-400 text-sm uppercase tracking-wide mb-2">End Date (UTC)</h3>
            <p class="text-xl font-mono">{{ $war->end_date ? $war->end_date->format('d M Y H:i') : 'Ongoing' }}</p>
        </div>

        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-gray-400 text-sm uppercase tracking-wide mb-2">War Duration</h3>
            <p class="text-2xl font-bold font-mono" id="duration-display"></p>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <h3 class="text-gray-400 text-sm uppercase tracking-wide mb-4">Final Score</h3>
        <div class="text-center">
            <div class="flex items-center justify-center space-x-8">
                <div>
                    <p class="text-5xl font-bold text-green-400">{{ $war->score_ours ?? 0 }}</p>
                    <p class="text-gray-400 mt-2">Our Faction</p>
                </div>
                <div class="text-3xl text-gray-500">-</div>
                <div>
                    <p class="text-5xl font-bold text-red-400">{{ $war->score_them ?? 0 }}</p>
                    <p class="text-gray-400 mt-2">{{ $war->opponent_faction_name ?? 'Opponent' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-gray-800 rounded-lg border border-gray-700">
            <div class="p-4 border-b border-gray-700 flex justify-between items-center">
                <h2 class="text-xl font-semibold text-green-400">Our Faction ({{ $war->score_ours ?? 0 }} pts)</h2>
                <select id="sort-our" class="bg-gray-700 text-gray-300 text-sm rounded px-2 py-1">
                    <option value="name-asc">Name A-Z</option>
                    <option value="name-desc">Name Z-A</option>
                    <option value="level-desc">Level High-Low</option>
                    <option value="level-asc">Level Low-High</option>
                    <option value="ff-desc">FF High-Low</option>
                    <option value="ff-asc">FF Low-High</option>
                    <option value="status-asc">Status</option>
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="sticky top-0 bg-gray-700">
                        <tr class="text-left text-gray-400 text-sm">
                            <th class="p-3">Name</th>
                            <th class="p-3">Level</th>
                            <th class="p-3 text-right">FF Score</th>
                            <th class="p-3 text-right">Est. Stats</th>
                            <th class="p-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700" id="tbody-our">
                        @foreach($ourMembers as $member)
                        <tr class="hover:bg-gray-700/30" data-name="{{ strtolower($member->name) }}" data-level="{{ $member->level }}" data-ff="{{ $member->ff_score ?? 0 }}" data-stats="{{ $member->estimated_stats ?? '' }}" data-status="{{ $member->status_description ?? '' }}">
                            <td class="p-3">
                                <span class="inline-block w-2 h-2 rounded-full mr-2
                                    @if($member->online_status === 'Online') bg-green-500
                                    @elseif($member->online_status === 'Idle') bg-yellow-500
                                    @else bg-gray-500
                                    @endif"></span>
                                <span class="font-medium">{{ $member->name }}</span>
                                <span class="text-gray-500 text-xs ml-1">#{{ $member->player_id }}</span>
                                @if($member->online_description)
                                    <span class="block text-xs text-gray-500 ml-4">Last action: {{ $member->online_description }}</span>
                                @endif
                            </td>
                            <td class="p-3">{{ $member->level }}</td>
                            <td class="p-3 text-right">
                                <span class="font-mono text-green-400">{{ $member->ff_score ?? '-' }}</span>
                                @if($member->ff_updated_at)
                                    <span class="block text-xs text-gray-500">{{ $member->ff_updated_at->diffForHumans() }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-right font-mono text-gray-400 text-sm">{{ $member->estimated_stats ?? '-' }}</td>
                            <td class="p-3">
                                <span class="inline-block w-2 h-2 rounded-full 
                                    @if($member->status_color === 'green') bg-green-500
                                    @elseif($member->status_color === 'blue') bg-blue-500
                                    @elseif($member->status_color === 'red') bg-red-500
                                    @else bg-gray-500
                                    @endif"></span>
                                <span class="text-xs text-gray-400 ml-1">{{ $member->status_description ?? 'Offline' }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg border border-gray-700">
            <div class="p-4 border-b border-gray-700 flex justify-between items-center">
                <h2 class="text-xl font-semibold text-red-400">{{ $war->opponent_faction_name ?? 'Opponent' }} ({{ $war->score_them ?? 0 }} pts)</h2>
                <select id="sort-opp" class="bg-gray-700 text-gray-300 text-sm rounded px-2 py-1">
                    <option value="name-asc">Name A-Z</option>
                    <option value="name-desc">Name Z-A</option>
                    <option value="level-desc">Level High-Low</option>
                    <option value="level-asc">Level Low-High</option>
                    <option value="ff-desc">FF High-Low</option>
                    <option value="ff-asc">FF Low-High</option>
                    <option value="status-asc">Status</option>
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="sticky top-0 bg-gray-700">
                        <tr class="text-left text-gray-400 text-sm">
                            <th class="p-3">Name</th>
                            <th class="p-3">Level</th>
                            <th class="p-3 text-right">FF Score</th>
                            <th class="p-3 text-right">Est. Stats</th>
                            <th class="p-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700" id="tbody-opp">
                        @foreach($opponentMembers as $member)
                        <tr class="hover:bg-gray-700/30" data-name="{{ strtolower($member->name) }}" data-level="{{ $member->level }}" data-ff="{{ $member->ff_score ?? 0 }}" data-stats="{{ $member->estimated_stats ?? '' }}" data-status="{{ $member->status_description ?? '' }}">
                            <td class="p-3">
                                <span class="inline-block w-2 h-2 rounded-full mr-2
                                    @if($member->online_status === 'Online') bg-green-500
                                    @elseif($member->online_status === 'Idle') bg-yellow-500
                                    @else bg-gray-500
                                    @endif"></span>
                                <span class="font-medium">{{ $member->name }}</span>
                                <span class="text-gray-500 text-xs ml-1">#{{ $member->player_id }}</span>
                                @if($member->online_description)
                                    <span class="block text-xs text-gray-500 ml-4">Last action: {{ $member->online_description }}</span>
                                @endif
                            </td>
                            <td class="p-3">{{ $member->level }}</td>
                            <td class="p-3 text-right">
                                <span class="font-mono text-red-400">{{ $member->ff_score ?? '-' }}</span>
                                @if($member->ff_updated_at)
                                    <span class="block text-xs text-gray-500">{{ $member->ff_updated_at->diffForHumans() }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-right font-mono text-gray-400 text-sm">{{ $member->estimated_stats ?? '-' }}</td>
                            <td class="p-3">
                                <span class="inline-block w-2 h-2 rounded-full 
                                    @if($member->status_color === 'green') bg-green-500
                                    @elseif($member->status_color === 'blue') bg-blue-500
                                    @elseif($member->status_color === 'red') bg-red-500
                                    @else bg-gray-500
                                    @endif"></span>
                                <span class="text-xs text-gray-400 ml-1">{{ $member->status_description ?? 'Offline' }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
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
                        <th class="p-4">Time (UTC)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($war->attacks as $attack)
                    <tr class="hover:bg-gray-700/30">
                        <td class="p-4">{{ $attack->attacker_name ?? 'Unknown' }}</td>
                        <td class="p-4">{{ $attack->defender_name ?? 'Unknown' }}</td>
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
                        <td class="p-4 text-gray-400 text-sm font-mono">
                            {{ $attack->created_at ? $attack->created_at->format('d M Y H:i') : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-400">
                            No attacks found for this war.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
(function() {
    const status = '{{ $war->status }}';
    const startTimestamp = {{ $war->start_date ? $war->start_date->timestamp : 0 }};
    const endTimestamp = {{ $war->end_date ? $war->end_date->timestamp : 0 }};
    
    function formatDuration(seconds) {
        const d = Math.floor(seconds / 86400);
        const h = Math.floor((seconds % 86400) / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        
        let result = '';
        if (d > 0) result += d + 'd ';
        if (h > 0 || d > 0) result += h + 'h ';
        if (m > 0 || h > 0 || d > 0) result += m + 'm ';
        result += s + 's';
        return result;
    }
    
    function updateTimer() {
        const now = Math.floor(Date.now() / 1000);
        const label = document.getElementById('timer-label');
        const display = document.getElementById('timer-display');
        const durationDisplay = document.getElementById('duration-display');
        
        if (status === 'pending') {
            const diff = startTimestamp - now;
            if (diff > 0) {
                label.textContent = 'Starts in';
                display.textContent = formatDuration(diff);
                display.className = 'text-2xl font-bold font-mono mt-2 text-yellow-400';
            } else {
                label.textContent = 'Started';
                display.textContent = formatDuration(Math.abs(diff)) + ' ago';
                display.className = 'text-2xl font-bold font-mono mt-2 text-green-400';
            }
        } else if (status === 'in progress') {
            const diff = now - startTimestamp;
            label.textContent = 'In progress for';
            display.textContent = formatDuration(diff);
            display.className = 'text-2xl font-bold font-mono mt-2 text-green-400';
        } else if (status === 'won' || status === 'lost') {
            display.textContent = '';
            label.textContent = '';
        }
        
        if ((status === 'won' || status === 'lost') && endTimestamp > 0 && startTimestamp > 0) {
            const duration = endTimestamp - startTimestamp;
            durationDisplay.textContent = formatDuration(duration);
        } else {
            durationDisplay.textContent = '--';
        }
    }
    
    updateTimer();
    setInterval(updateTimer, 1000);

    function parseStats(str) {
        if (!str) return 0;
        const num = parseFloat(str);
        if (str.includes('b')) return num * 1000000000;
        if (str.includes('m')) return num * 1000000;
        if (str.includes('k')) return num * 1000;
        return num;
    }

    function sortTable(tbodyId, selectId) {
        const tbody = document.getElementById(tbodyId);
        const select = document.getElementById(selectId);
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const [field, dir] = select.value.split('-');
        
        rows.sort((a, b) => {
            let aVal, bVal;
            if (field === 'name') {
                aVal = a.dataset.name;
                bVal = b.dataset.name;
            } else if (field === 'level') {
                aVal = parseInt(a.dataset.level);
                bVal = parseInt(b.dataset.level);
            } else if (field === 'ff') {
                aVal = parseFloat(a.dataset.ff);
                bVal = parseFloat(b.dataset.ff);
            } else if (field === 'stats') {
                aVal = parseStats(a.dataset.stats);
                bVal = parseStats(b.dataset.stats);
            } else if (field === 'status') {
                aVal = a.dataset.status || '';
                bVal = b.dataset.status || '';
            }
            
            if (typeof aVal === 'string') {
                return dir === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
            }
            return dir === 'asc' ? aVal - bVal : bVal - aVal;
        });
        
        rows.forEach(row => tbody.appendChild(row));
    }

    document.getElementById('sort-our').addEventListener('change', () => sortTable('tbody-our', 'sort-our'));
    document.getElementById('sort-opp').addEventListener('change', () => sortTable('tbody-opp', 'sort-opp'));
})();
</script>
@endsection
