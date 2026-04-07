@extends('layouts.app')

@section('title', 'Scheduled Jobs - TornOps')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold">Scheduled Jobs</h1>
        <form action="/admin/scheduled-jobs/seed" method="POST">
            @csrf
            <button type="submit" class="bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded text-white text-sm">
                Re-seed Jobs
            </button>
        </form>
    </div>

    @if(session('status'))
        <div class="bg-green-900/50 border border-green-700 text-green-400 px-4 py-3 rounded">
            {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-900/50 border border-red-700 text-red-400 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-700 text-gray-300">
                <tr>
                    <th class="px-3 py-3 text-left text-sm font-semibold">Command</th>
                    <th class="px-3 py-3 text-left text-sm font-semibold">Description</th>
                    <th class="px-3 py-3 text-center text-sm font-semibold">Enabled</th>
                    <th class="px-3 py-3 text-left text-sm font-semibold">
                        <div class="flex gap-1 items-center">
                            <span class="w-24">Schedule (Peace)</span>
                            <span class="w-16 text-center">Hr</span>
                            <span class="w-16 text-center">Min</span>
                        </div>
                    </th>
                    <th class="px-3 py-3 text-center text-sm font-semibold">War Only</th>
                    <th class="px-3 py-3 text-center text-sm font-semibold">War Enabled</th>
                    <th class="px-3 py-3 text-left text-sm font-semibold">
                        <div class="flex gap-1 items-center">
                            <span class="w-24">Schedule (War)</span>
                            <span class="w-16 text-center">Hr</span>
                            <span class="w-16 text-center">Min</span>
                        </div>
                    </th>
                    <th class="px-3 py-3 text-center text-sm font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @foreach($jobs as $job)
                <tr class="hover:bg-gray-750">
                    <form action="/admin/scheduled-jobs/{{ $job->id }}" method="POST">
                        @csrf
                        @method('PUT')
                        <td class="px-3 py-3">
                            <code class="text-sm text-yellow-400">{{ $job->command }}</code>
                        </td>
                        <td class="px-3 py-3 text-gray-300 text-sm">
                            {{ $job->description }}
                        </td>
                        <td class="px-3 py-3 text-center">
                            <input type="checkbox" name="enabled" value="1" {{ $job->enabled ? 'checked' : '' }}
                                   class="w-5 h-5 rounded bg-gray-700 border-gray-600">
                        </td>
                        <td class="px-3 py-3">
                            <div class="flex gap-1 items-center">
                                <select onchange="applyPreset(this, 'cron')" 
                                        class="bg-gray-700 border border-gray-600 rounded px-2 py-1 text-white text-sm w-24">
                                    <option value="">Custom</option>
                                    <option value="*/5">Every 5</option>
                                    <option value="*/10">Every 10</option>
                                    <option value="*/15">Every 15</option>
                                    <option value="*/30">Every 30</option>
                                    <option value="0">Hourly</option>
                                    <option value="0">Daily</option>
                                </select>
                                <input type="number" name="cron_hour" min="0" max="23" value="{{ explode(' ', $job->cron_expression)[1] ?? '*' }}" 
                                       class="w-16 bg-gray-700 border border-gray-600 rounded px-2 py-1 text-white text-sm text-center font-mono"
                                       title="Hour (0-23 or */n for every n hours)">
                                <span class="text-gray-500">:</span>
                                <input type="number" name="cron_min" min="0" max="59" value="{{ explode(' ', $job->cron_expression)[0] ?? '*' }}" 
                                       class="w-16 bg-gray-700 border border-gray-600 rounded px-2 py-1 text-white text-sm text-center font-mono"
                                       title="Minute (0-59 or */n for every n minutes)">
                            </div>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <input type="checkbox" name="war_mode_only" value="1" {{ $job->war_mode_only ? 'checked' : '' }}
                                   class="w-5 h-5 rounded bg-gray-700 border-gray-600" onchange="toggleWarFields(this)">
                        </td>
                        <td class="px-3 py-3 text-center">
                            <input type="checkbox" name="war_enabled" value="1" {{ $job->war_enabled ? 'checked' : '' }}
                                   class="w-5 h-5 rounded bg-gray-700 border-gray-600"
                                   {{ !$job->war_mode_only ? 'disabled' : '' }}>
                        </td>
                        <td class="px-3 py-3">
                            <div class="flex gap-1 items-center">
                                <select onchange="applyPreset(this, 'war')"
                                        class="bg-gray-700 border border-gray-600 rounded px-2 py-1 text-white text-sm w-24"
                                        {{ !$job->war_mode_only ? 'disabled' : '' }}>
                                    <option value="">Custom</option>
                                    <option value="*/1">Every 1</option>
                                    <option value="*/2">Every 2</option>
                                    <option value="*/5">Every 5</option>
                                    <option value="*/10">Every 10</option>
                                    <option value="*/15">Every 15</option>
                                </select>
                                <input type="number" name="war_hour" min="0" max="23" value="{{ $job->war_cron ? explode(' ', $job->war_cron)[1] : '' }}" 
                                       class="w-16 bg-gray-700 border border-gray-600 rounded px-2 py-1 text-white text-sm text-center font-mono"
                                       title="Hour (0-23 or */n for every n hours)"
                                       {{ !$job->war_mode_only ? 'disabled' : '' }}>
                                <span class="text-gray-500">:</span>
                                <input type="number" name="war_min" min="0" max="59" value="{{ $job->war_cron ? explode(' ', $job->war_cron)[0] : '' }}" 
                                       class="w-16 bg-gray-700 border border-gray-600 rounded px-2 py-1 text-white text-sm text-center font-mono"
                                       title="Minute (0-59 or */n for every n minutes)"
                                       {{ !$job->war_mode_only ? 'disabled' : '' }}>
                            </div>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-white text-sm">
                                Save
                            </button>
                        </td>
                    </form>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <h3 class="text-lg font-semibold text-gray-300 mb-2">Cron Format Help</h3>
        <div class="text-sm text-gray-400">
            <p class="mb-2"><code class="text-yellow-400">Hr:Min * * *</code> = hour(0-23) minute(0-59) day(1-31) month(1-12) weekday(0-6)</p>
            <p class="mb-2">Use <code class="text-yellow-400">*</code> for every value.</p>
            <p class="mb-2">Use <code class="text-yellow-400">*/n</code> for every n (e.g., <code class="text-yellow-400">*/5</code> = every 5).</p>
            <p>Examples: <code class="text-yellow-400">* */10</code> = every 10 min, <code class="text-yellow-400">* 0</code> = every hour at minute 0, <code class="text-yellow-400">0 0</code> = daily at midnight</p>
        </div>
    </div>
</div>

<script>
function applyPreset(select, type) {
    const row = select.closest('tr');
    const minInput = row.querySelector(`input[name="${type}_min"]`);
    const hourInput = row.querySelector(`input[name="${type}_hour"]`);
    
    if (select.value) {
        minInput.value = select.value;
        hourInput.value = '*';
    }
}

function toggleWarFields(checkbox) {
    const row = checkbox.closest('tr');
    const warEnabled = row.querySelector('input[name="war_enabled"]');
    const warMin = row.querySelector('input[name="war_min"]');
    const warHour = row.querySelector('input[name="war_hour"]');
    const warPreset = row.querySelector('select[name="war_preset"]');
    
    if (checkbox.checked) {
        warEnabled.disabled = false;
        warMin.disabled = false;
        warHour.disabled = false;
        if (warPreset) warPreset.disabled = false;
    } else {
        warEnabled.disabled = true;
        warEnabled.checked = false;
        warMin.disabled = true;
        warMin.value = '';
        warHour.disabled = true;
        warHour.value = '';
        if (warPreset) {
            warPreset.disabled = true;
            warPreset.value = '';
        }
    }
}
</script>
@endsection
