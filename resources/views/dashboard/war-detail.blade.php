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
        <div class="flex items-center gap-3">
            @if($war->status === 'won')
                <span class="px-4 py-2 rounded bg-green-900 text-green-400 font-semibold">Won</span>
            @elseif($war->status === 'lost')
                <span class="px-4 py-2 rounded bg-red-900 text-red-400 font-semibold">Lost</span>
            @else
                <span class="px-4 py-2 rounded bg-yellow-900 text-yellow-400 font-semibold">{{ ucfirst($war->status) }}</span>
            @endif
            @if(in_array($war->status, ['in progress', 'pending']))
                <span id="live-indicator" class="flex items-center gap-1.5 px-3 py-1 rounded bg-green-900/50 text-green-400 text-sm font-medium">
                    <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                    LIVE
                </span>
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
        <h3 class="text-gray-400 text-sm uppercase tracking-wide mb-4">War Progress</h3>
        <div class="text-center">
            <div class="flex items-center justify-center space-x-8">
                <div>
                    <p class="text-5xl font-bold text-green-400" id="score-ours">{{ $war->score_ours ?? 0 }}</p>
                    <p class="text-gray-400 mt-2">Our Faction</p>
                </div>
                <div class="text-3xl text-gray-500">-</div>
                <div>
                    <p class="text-5xl font-bold text-red-400" id="score-them">{{ $war->score_them ?? 0 }}</p>
                    <p class="text-gray-400 mt-2">{{ $war->opponent_faction_name ?? 'Opponent' }}</p>
                </div>
            </div>
        </div>
        @php
            $target = $war->data['war']['target'] ?? 1900;
            $ours = $war->score_ours ?? 0;
            $them = $war->score_them ?? 0;
            $diff = $ours - $them;
            $combinedTarget = $target * 2;
            $percent = round((0.5 + ($diff / $combinedTarget)) * 100, 1);
            $width = min(100, max(0, $percent));
            $moreToWin = $diff >= 0 ? ($target - $diff) : ($target - $diff);
        @endphp
        <div class="mt-6">
            <div class="flex justify-between text-sm text-gray-400 mb-2">
                <span>Target: {{ $target }} pts</span>
                <span id="progress-percent">{{ $percent }}%</span>
            </div>
            <div class="w-full bg-gray-700 rounded-full h-4">
                <div class="{{ $diff >= 0 ? 'bg-green-500' : 'bg-yellow-500' }} h-4 rounded-full transition-all duration-300" style="width: {{ $width }}%"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span class="{{ $diff >= 0 ? 'text-green-400' : 'text-yellow-400' }}">{{ $diff >= 0 ? '+' . $diff : $diff }} pts lead</span>
                <span>{{ $moreToWin }} more to win</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-gray-800 rounded-lg border border-gray-700">
            <div class="p-4 border-b border-gray-700">
                <h2 class="text-xl font-semibold text-green-400" id="header-our">Our Faction (<span id="pts-our">{{ $war->score_ours ?? 0 }}</span> pts)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
<thead class="sticky top-0 bg-gray-700 cursor-pointer select-none" id="thead-our">
                        <tr class="text-left text-gray-400 text-sm">
                            <th class="p-3" data-sort="name" data-dir="asc">Name <span class="sort-icon text-gray-500">↑</span></th>
                            <th class="p-3" data-sort="level" data-dir="desc">Level <span class="sort-icon text-gray-500">↓</span></th>
                            <th class="p-3 text-right" data-sort="ff" data-dir="desc">FF Score <span class="sort-icon text-gray-500">↓</span></th>
                            <th class="p-3 text-right" data-sort="stats" data-dir="desc">Est. Stats <span class="sort-icon text-gray-500">↓</span></th>
                            <th class="p-3 text-right" data-sort="hits" data-dir="desc">Hits <span class="sort-icon text-gray-500">↓</span></th>
                            <th class="p-3 text-right" data-sort="pwar" data-dir="desc">War Score <span class="sort-icon text-gray-500">↓</span></th>
                            <th class="p-3" data-sort="status" data-dir="asc">Status <span class="sort-icon text-gray-500">↑</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700" id="tbody-our">
                        @foreach($ourMembers as $member)
                        @php
                            $stats = $attackStats[$member->player_id] ?? null;
                            $hits = $stats->total_attacks ?? 0;
                            $successful = $stats->successful ?? 0;
                            $failed = $stats->failed ?? 0;
                            $interrupted = $stats->interrupted ?? 0;
                            $warScore = $stats->total_score ?? 0;
                        @endphp
                        <tr class="hover:bg-gray-700/30" data-player-id="{{ $member->player_id }}" data-name="{{ strtolower($member->name) }}" data-level="{{ $member->level }}" data-ff="{{ $member->ff_score ?? 0 }}" data-stats="{{ $member->estimated_stats ?? '' }}" data-status="{{ $member->status_description ?? '' }}" data-hits="{{ $hits }}" data-warscore="{{ $warScore }}">
                            <td class="p-3">
                                <span class="inline-block w-2 h-2 rounded-full mr-2
                                    @if($member->online_status === 'Online') bg-green-500
                                    @elseif($member->online_status === 'Idle') bg-yellow-500
                                    @else bg-gray-500
                                    @endif"></span>
                                <span class="font-medium">{{ $member->name }}</span>
                                <span class="text-gray-500 text-xs ml-1">#{{ $member->player_id }}</span>
                                @if($member->online_description)
                                    <span class="block text-xs text-gray-500 ml-4 last-action">Last action: {{ $member->online_description }}</span>
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
                            <td class="p-3 text-right">
                                <span class="font-mono text-blue-400 text-lg cursor-help" title="War Stats:&#10;Total: {{ $hits }}&#10;Success: {{ $successful }}&#10;Fail: {{ $failed }}&#10;Interrupt: {{ $interrupted }}&#10;Score: {{ $warScore > 0 ? round($warScore, 2) : '0.00' }}">{{ $hits }}</span>
                                <span class="block text-xs text-gray-500">S:{{ $successful }} F:{{ $failed }} I:{{ $interrupted }}</span>
                            </td>
                            <td class="p-3 text-right font-mono text-purple-400">{{ $warScore > 0 ? round($warScore, 1) : '-' }}</td>
                            <td class="p-3">
                                @if($member->status_color === 'red' && isset($member->data['status']['until']))
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-red-900/50 text-red-400 text-xs font-medium hospital-timer cursor-help" data-until="{{ $member->data['status']['until'] }}" title="{{ $member->status_description ?? 'In hospital' }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400 animate-pulse"></span>
                                        <span class="hospital-desc">{{ $member->status_description ?? 'Hospital' }}</span>
                                    </span>
                                @elseif($member->status_color === 'blue')
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-blue-900/50 text-blue-400 text-xs font-medium travel-bubble cursor-help" 
                                          title="{{ $member->status_description ?? 'Traveling' }}"
                                          data-status-changed="{{ $member->status_changed_at?->timestamp }}"
                                          data-travel-time="60">
                                        <span class="torn-icon" style="display:inline-block;width:14px;height:14px;border:1px solid currentColor;border-radius:50%;text-align:center;line-height:12px;font-size:10px;">T</span>
                                        <svg class="w-3 h-3 plane-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/></svg>
                                        <span class="travel-desc" data-original="{{ $member->status_description ?? 'Offline' }}"></span>
                                        <span class="travel-eta ml-1 font-mono"></span>
                                    </span>
                                @elseif($member->status_color === 'green')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-green-900/50 text-green-400 text-xs font-medium cursor-help" title="{{ $member->status_description ?? 'Okay' }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                        <span class="status-desc">{{ $member->status_description ?? 'Okay' }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-gray-700/50 text-gray-400 text-xs font-medium cursor-help" title="{{ $member->status_description ?? 'Offline' }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span>
                                        <span class="status-desc">{{ $member->status_description ?? 'Offline' }}</span>
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg border border-gray-700">
            <div class="p-4 border-b border-gray-700">
                <h2 class="text-xl font-semibold text-red-400" id="header-opp">{{ $war->opponent_faction_name ?? 'Opponent' }} (<span id="pts-opp">{{ $war->score_them ?? 0 }}</span> pts)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="sticky top-0 bg-gray-700 cursor-pointer select-none" id="thead-opp">
                        <tr class="text-left text-gray-400 text-sm">
                            <th class="p-3" data-sort="name" data-dir="asc">Name <span class="sort-icon text-gray-500">↑</span></th>
                            <th class="p-3" data-sort="level" data-dir="desc">Level <span class="sort-icon text-gray-500">↓</span></th>
                            <th class="p-3 text-right" data-sort="ff" data-dir="desc">FF Score <span class="sort-icon text-gray-500">↓</span></th>
                            <th class="p-3 text-right" data-sort="stats" data-dir="desc">Est. Stats <span class="sort-icon text-gray-500">↓</span></th>
                            <th class="p-3 text-right" data-sort="hits" data-dir="desc">Hits <span class="sort-icon text-gray-500">↓</span></th>
                            <th class="p-3" data-sort="status" data-dir="asc">Status <span class="sort-icon text-gray-500">↑</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700" id="tbody-opp">
                        @foreach($opponentMembers as $member)
                        @php
                            $stats = $attackStats[$member->player_id] ?? null;
                            $hits = $stats->total_attacks ?? 0;
                            $successful = $stats->successful ?? 0;
                            $failed = $stats->failed ?? 0;
                            $interrupted = $stats->interrupted ?? 0;
                        @endphp
                        <tr class="hover:bg-gray-700/30" data-player-id="{{ $member->player_id }}" data-name="{{ strtolower($member->name) }}" data-level="{{ $member->level }}" data-ff="{{ $member->ff_score ?? 0 }}" data-stats="{{ $member->estimated_stats ?? '' }}" data-status="{{ $member->status_description ?? '' }}" data-hits="{{ $hits }}">
                            <td class="p-3">
                                <span class="inline-block w-2 h-2 rounded-full mr-2
                                    @if($member->online_status === 'Online') bg-green-500
                                    @elseif($member->online_status === 'Idle') bg-yellow-500
                                    @else bg-gray-500
                                    @endif"></span>
                                <span class="font-medium">{{ $member->name }}</span>
                                <span class="text-gray-500 text-xs ml-1">#{{ $member->player_id }}</span>
                                @if($member->online_description)
                                    <span class="block text-xs text-gray-500 ml-4 last-action">Last action: {{ $member->online_description }}</span>
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
                            <td class="p-3 text-right">
                                <span class="font-mono text-blue-400 text-lg cursor-help" title="War Stats:&#10;Total: {{ $hits }}&#10;Success: {{ $successful }}&#10;Fail: {{ $failed }}&#10;Interrupt: {{ $interrupted }}">{{ $hits }}</span>
                                <span class="block text-xs text-gray-500">S:{{ $successful }} F:{{ $failed }} I:{{ $interrupted }}</span>
                            </td>
                            <td class="p-3">
                                @if($member->status_color === 'red' && isset($member->data['status']['until']))
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-red-900/50 text-red-400 text-xs font-medium hospital-timer cursor-help" data-until="{{ $member->data['status']['until'] }}" title="{{ $member->status_description ?? 'In hospital' }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400 animate-pulse"></span>
                                        <span class="hospital-desc">{{ $member->status_description ?? 'Hospital' }}</span>
                                    </span>
                                @elseif($member->status_color === 'blue')
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-blue-900/50 text-blue-400 text-xs font-medium travel-bubble cursor-help" 
                                          title="{{ $member->status_description ?? 'Traveling' }}"
                                          data-status-changed="{{ $member->status_changed_at?->timestamp }}"
                                          data-travel-time="60">
                                        <span class="torn-icon" style="display:inline-block;width:14px;height:14px;border:1px solid currentColor;border-radius:50%;text-align:center;line-height:12px;font-size:10px;">T</span>
                                        <svg class="w-3 h-3 plane-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/></svg>
                                        <span class="travel-desc" data-original="{{ $member->status_description ?? 'Offline' }}"></span>
                                        <span class="travel-eta ml-1 font-mono"></span>
                                    </span>
                                @elseif($member->status_color === 'green')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-green-900/50 text-green-400 text-xs font-medium cursor-help" title="{{ $member->status_description ?? 'Okay' }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                        <span class="status-desc">{{ $member->status_description ?? 'Okay' }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-gray-700/50 text-gray-400 text-xs font-medium cursor-help" title="{{ $member->status_description ?? 'Offline' }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span>
                                        <span class="status-desc">{{ $member->status_description ?? 'Offline' }}</span>
                                    </span>
                                @endif
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
                        <th class="p-4">Result</th>
                        <th class="p-4">Defender</th>
                        <th class="p-4">Score</th>
                        <th class="p-4">Time (UTC)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700" id="war-attacks-table">
                    @forelse($war->attacks as $attack)
                    <tr class="hover:bg-gray-700/30">
                        <td class="p-4{{ $attack->attacker_name === 'Stealthed' ? ' italic text-gray-500' : '' }}">{{ $attack->attacker_name ?? 'Unknown' }}</td>
                        <td class="p-4">
                            @if($attack->result === 'Attacked' || $attack->result === 'Hospitalized')
                                <span class="px-2 py-1 rounded text-xs bg-green-900 text-green-400">{{ $attack->result }}</span>
                            @elseif($attack->result === 'Lost' || $attack->result === 'Stalemate')
                                <span class="px-2 py-1 rounded text-xs bg-red-900 text-red-400">{{ $attack->result }}</span>
                            @elseif($attack->result === 'Interrupted')
                                <span class="px-2 py-1 rounded text-xs bg-yellow-900 text-yellow-400">{{ $attack->result }}</span>
                            @else
                                <span class="px-2 py-1 rounded text-xs bg-gray-700 text-gray-400">{{ $attack->result ?? '-' }}</span>
                            @endif
                        </td>
                        <td class="p-4">{{ $attack->defender_name ?? 'Unknown' }}</td>
                        <td class="p-4 font-mono">{{ $attack->respect_gain ?? 0 }}</td>
                        <td class="p-4 text-gray-400 text-sm font-mono">
                            {{ $attack->timestamp ? $attack->timestamp->format('d M Y H:i:s') : '-' }}
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
'use strict';
var status = '{{ $war->status }}';
var startTimestamp = {{ $war->start_date ? $war->start_date->timestamp : 0 }};
var endTimestamp = {{ $war->end_date ? $war->end_date->timestamp : 0 }};
var warId = {{ $war->war_id }};
var isActiveWar = ['in progress', 'pending'].indexOf(status) !== -1;

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
} else if (status === 'in progress') {
durationDisplay.textContent = formatDuration(now - startTimestamp);
} else {
durationDisplay.textContent = '--';
}
}

function formatTime(seconds) {
var h = Math.floor(seconds / 3600);
var m = Math.floor((seconds % 3600) / 60);
var s = seconds % 60;
if (h > 0) {
return h + ':' + m.toString().padStart(2, '0') + ':' + s.toString().padStart(2, '0');
}
return m + ':' + s.toString().padStart(2, '0');
}

function getHospitalCountry(title) {
var countryMap = {
'South African': 'SA', 'South Africa': 'SA',
'United Kingdom': 'UK', 'UK': 'UK',
'United Arab Emirates': 'UAE', 'UAE': 'UAE',
'United States': 'USA', 'USA': 'USA',
'Canada': 'CA', 'Cayman Islands': 'CA',
'Mexico': 'MX', 'Japan': 'JP',
'China': 'CN', 'Hong Kong': 'HK',
'Argentina': 'AR', 'Brazil': 'BR',
'Switzerland': 'CH', 'Germany': 'DE',
'France': 'FR', 'Italy': 'IT',
'Netherlands': 'NL', 'Spain': 'ES',
'Australia': 'AU', 'New Zealand': 'NZ',
'India': 'IN'
};
for (var full in countryMap) {
if (title && title.indexOf(full) !== -1) {
return countryMap[full];
}
}
return null;
}

function updateHospitalTimers() {
var now = Math.floor(Date.now() / 1000);
var elements = document.querySelectorAll('.hospital-timer');
for (var i = 0; i < elements.length; i++) {
var el = elements[i];
var until = parseInt(el.getAttribute('data-until'));
if (isNaN(until)) continue;

var desc = el.querySelector('.hospital-desc');
var pulse = el.querySelector('.animate-pulse');
var title = el.getAttribute('title') || '';
var country = getHospitalCountry(title);

if (now < until) {
var remaining = until - now;
var prefix = country ? 'Hospital in ' + country : 'Hospital';
if (desc) desc.textContent = prefix + ' (' + formatTime(remaining) + ')';
if (pulse) pulse.style.display = 'inline-block';
} else {
if (desc) desc.textContent = 'Released';
if (pulse) pulse.style.display = 'none';
el.classList.remove('bg-red-900/50', 'text-red-400');
el.classList.add('bg-green-900/50', 'text-green-400');
}
}
}

function updateTravelTimers() {
var now = Math.floor(Date.now() / 1000);
var bubbles = document.querySelectorAll('.travel-bubble');
for (var i = 0; i < bubbles.length; i++) {
var bubble = bubbles[i];
var statusChanged = parseInt(bubble.getAttribute('data-status-changed'));
var travelTime = parseInt(bubble.getAttribute('data-travel-time')) || 60;
var etaEl = bubble.querySelector('.travel-eta');

if (!etaEl) continue;

if (isNaN(statusChanged)) {
etaEl.textContent = '';
continue;
}

var travelTimeSec = travelTime * 60;
var elapsed = now - statusChanged;
var remaining = travelTimeSec - elapsed;

if (remaining > 0) {
var h = Math.floor(remaining / 3600);
var m = Math.floor((remaining % 3600) / 60);
var s = remaining % 60;
if (h > 0) {
etaEl.textContent = '(' + h + ':' + m.toString().padStart(2, '0') + ':' + s.toString().padStart(2, '0') + ')';
} else {
etaEl.textContent = '(' + m + ':' + s.toString().padStart(2, '0') + ')';
}
} else {
etaEl.textContent = '';
}
}
}

function parseStats(str) {
if (!str) return 0;
const num = parseFloat(str);
if (str.includes('b')) return num * 1000000000;
if (str.includes('m')) return num * 1000000;
if (str.includes('k')) return num * 1000;
return num;
}

function sortTable(tbody, field, dir) {
const rows = Array.from(tbody.querySelectorAll('tr'));

rows.sort((a, b) => {
let aVal, bVal;
if (field === 'name') {
aVal = a.dataset.name;
bVal = b.dataset.name;
} else if (field === 'level') {
aVal = parseInt(a.dataset.level);
bVal = parseInt(b.dataset.level);
} else if (field === 'ff') {
aVal = parseFloat(a.dataset.ff) || 0;
bVal = parseFloat(b.dataset.ff) || 0;
} else if (field === 'stats') {
aVal = parseStats(a.dataset.stats);
bVal = parseStats(b.dataset.stats);
} else if (field === 'pwar') {
aVal = parseFloat(a.dataset.pwar) || 0;
bVal = parseFloat(b.dataset.pwar) || 0;
} else if (field === 'hits') {
aVal = parseInt(a.dataset.hits) || 0;
bVal = parseInt(b.dataset.hits) || 0;
} else if (field === 'warscore') {
aVal = parseFloat(a.dataset.warscore) || 0;
bVal = parseFloat(b.dataset.warscore) || 0;
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

function getUrlParams() {
const params = new URLSearchParams(window.location.search);
return {
sort: params.get('sort') || 'level',
dir: params.get('dir') || 'desc'
};
}

function updateUrlSort(sort, dir) {
const params = new URLSearchParams(window.location.search);
params.set('sort', sort);
params.set('dir', dir);
const newUrl = window.location.pathname + '?' + params.toString();
window.history.replaceState({path: newUrl}, '', newUrl);
}

function updateAllTheads(field, dir) {
['thead-our', 'thead-opp'].forEach(theadId => {
const thead = document.getElementById(theadId);
if (!thead) return;
thead.querySelectorAll('th').forEach(h => {
if (h.dataset.sort === field) {
h.dataset.dir = dir;
h.querySelector('.sort-icon').textContent = dir === 'asc' ? '↑' : '↓';
h.querySelector('.sort-icon').className = 'sort-icon';
} else {
h.dataset.dir = h.dataset.sort === 'name' || h.dataset.sort === 'status' ? 'asc' : 'desc';
h.querySelector('.sort-icon').textContent = h.dataset.dir === 'asc' ? '↑' : '↓';
h.querySelector('.sort-icon').className = 'sort-icon text-gray-500';
}
});
});
}

function handleSortClick(th) {
const field = th.dataset.sort;
const newDir = currentSort.field === field && currentSort.dir === 'desc' ? 'asc' : 'desc';

currentSort.field = field;
currentSort.dir = newDir;

updateAllTheads(field, newDir);
sortTable(document.getElementById('tbody-our'), field, newDir);
sortTable(document.getElementById('tbody-opp'), field, newDir);
updateUrlSort(currentSort.field, currentSort.dir);
}

function simplifyTravelStatus(original) {
const countryMap = {
'United Kingdom': 'UK',
'United Arab Emirates': 'UAE',
'UAE': 'UAE',
'United States': 'USA',
'USA': 'USA',
'South Africa': 'SA',
'New Zealand': 'NZ',
'Hong Kong': 'HK',
'Switzerland': 'CH',
'Mexico': 'MX',
'China': 'CN',
'Japan': 'JP',
'Argentina': 'AR',
'Canada': 'CA',
'Germany': 'DE',
'France': 'FR',
'Italy': 'IT',
'Spain': 'ES',
'Netherlands': 'NL',
'Brazil': 'BR',
'India': 'IN',
'Australia': 'AU'
};

const travelTimes = {
        'UK': 111, 'UAE': 190, 'USA': 90, 'SA': 208,
        'NZ': 120, 'HK': 105, 'CH': 123, 'MX': 18,
        'CN': 169, 'JP': 158, 'AR': 117, 'CA': 29,
        'DE': 45, 'FR': 40, 'IT': 50, 'ES': 35,
        'NL': 35, 'BR': 60, 'IN': 75, 'AU': 120,
        'Cayman Islands': 25, 'Cayman': 25, 'Hawaii': 94
    };

let country = null;
for (const [full, short] of Object.entries(countryMap)) {
if (original.includes(full)) {
country = short;
break;
}
}

if (!country) {
const match = original.match(/(?:In |Returning to Torn from |Traveling to )(.*)/);
if (match) country = match[1];
}

const travelTime = travelTimes[country] || 60;

if (original.startsWith('Returning to Torn from') && country) {
return { direction: 'left', country, isTraveling: true, travelTime };
}

if (original.startsWith('Traveling to ') && country) {
return { direction: 'right', country, isTraveling: true, travelTime };
}

if (original.startsWith('In ') && country) {
return { direction: 'abroad', country, isTraveling: false };
}

return { direction: 'right', country: original, isTraveling: false };
}

function initTravelBubbles() {
document.querySelectorAll('.travel-desc').forEach(el => {
var original = el.getAttribute('data-original');
if (!original) return;
var result = simplifyTravelStatus(original);
var bubble = el.closest('.travel-bubble');
if (!bubble) return;

var planeIcon = bubble.querySelector('.plane-icon');
var tornIcon = bubble.querySelector('.torn-icon');

bubble.dataset.travelTime = result.travelTime;

if (result.isTraveling) {
if (planeIcon) {
planeIcon.style.display = 'inline';
if (result.direction === 'left') {
planeIcon.style.transform = 'rotate(-90deg)';
} else {
planeIcon.style.transform = 'rotate(90deg)';
}
}
if (tornIcon) tornIcon.style.display = 'inline';
el.textContent = result.country;
} else {
if (planeIcon) planeIcon.style.display = 'none';
if (tornIcon) tornIcon.style.display = 'none';
el.textContent = 'In ' + result.country;
}
});
}

const urlParams = getUrlParams();
let currentSort = { field: urlParams.sort, dir: urlParams.dir };

document.getElementById('thead-our').querySelectorAll('th').forEach(th => {
th.addEventListener('click', () => handleSortClick(th));
});
document.getElementById('thead-opp').querySelectorAll('th').forEach(th => {
th.addEventListener('click', () => handleSortClick(th));
});

updateAllTheads(currentSort.field, currentSort.dir);
sortTable(document.getElementById('tbody-our'), currentSort.field, currentSort.dir);
sortTable(document.getElementById('tbody-opp'), currentSort.field, currentSort.dir);

updateTimer();
setInterval(updateTimer, 1000);

updateHospitalTimers();
setInterval(updateHospitalTimers, 1000);

initTravelBubbles();
updateTravelTimers();
setInterval(updateTravelTimers, 1000);

if (isActiveWar) {
setInterval(function() {
location.reload();
}, 30000);

(function() {
var lastSyncKey = 'lastRealtimeSync_' + warId;
var syncInterval = 32000;

function playAlert() {
try {
var ctx = new (window.AudioContext || window.webkitAudioContext)();
var osc = ctx.createOscillator();
var gain = ctx.createGain();
osc.connect(gain);
gain.connect(ctx.destination);
osc.frequency.value = 880;
osc.type = 'sine';
gain.gain.setValueAtTime(0.3, ctx.currentTime);
gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
osc.start(ctx.currentTime);
osc.stop(ctx.currentTime + 0.3);
} catch (e) {}
}

function syncRealtime() {
var now = Date.now();
var lastSync = parseInt(localStorage.getItem(lastSyncKey) || '0', 10);
if (now - lastSync < 60000) {
return;
}
var lastAttackCount = parseInt(localStorage.getItem('lastAttackCount_' + warId) || '0', 10);
localStorage.setItem(lastSyncKey, now.toString());
fetch('/api/wars/' + warId + '/attacks')
.then(function(r) { return r.json(); })
.then(function(data) {
if (data.attacks && data.attacks.length > lastAttackCount && lastAttackCount > 0) {
playAlert();
}
localStorage.setItem('lastAttackCount_' + warId, data.attacks ? data.attacks.length : '0');
fetch('/api/wars/' + warId + '/live')
.catch(function() {});
})
.catch(function() {});
}

syncRealtime();
setInterval(syncRealtime, syncInterval);
})();
}
})();
</script>
@endsection
