@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white">Merit Planner</h1>
            <p class="text-gray-400 mt-1">Plan and track your merit allocation</p>
        </div>
    </div>

    @if(session('error'))
        <div class="bg-red-900/50 border border-red-700 text-red-200 px-4 py-3 rounded mb-6">
            {{ session('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="bg-green-900/50 border border-green-700 text-green-200 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(!$hasData)
        <div class="bg-gray-800 rounded-lg p-8 text-center border border-gray-700">
            <h2 class="text-xl font-semibold text-white mb-2">No Merit Data</h2>
            <p class="text-gray-400 mb-6">Fetch your merits from Torn to get started.</p>
            <form action="{{ route('merits.fetch') }}" method="POST">
                @csrf
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                    Fetch from API
                </button>
            </form>
        </div>
    @else
        <div class="bg-gray-800 rounded-lg p-6 mb-6 border border-gray-700">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center space-x-8">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-400">{{ $availablePoints }}</div>
                        <div class="text-sm text-gray-400">Available</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-400">{{ $usedPoints }}</div>
                        <div class="text-sm text-gray-400">Used</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-400">{{ $totalPlannedCost }}</div>
                        <div class="text-sm text-gray-400">Planned Cost</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold {{ $extraNeeded > 0 ? 'text-red-400' : 'text-green-400' }}">
                            {{ $extraNeeded > 0 ? '+' . $extraNeeded : '0' }}
                        </div>
                        <div class="text-sm text-gray-400">Extra Needed</div>
                    </div>
                </div>
                <div class="flex space-x-3">
                    <form action="{{ route('merits.fetch') }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-medium">
                            Fetch from API
                        </button>
                    </form>
                    <form action="{{ route('merits.reset') }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded font-medium">
                            Reset to Current
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @foreach($groupedMerits as $categoryName => $merits)
            @if(count($merits) > 0)
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        {{ $categoryName }}
                    </h2>
                    
                    <div class="space-y-4">
                        @foreach($merits as $merit)
                            <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <h3 class="text-lg font-semibold text-white">{{ $merit['name'] }}</h3>
                                        <p class="text-sm text-gray-400">{{ $merit['description'] }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-gray-900/50 rounded p-3">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-300">Current</span>
                                            <span class="text-sm text-gray-400">{{ $merit['current_level'] }}/10</span>
                                        </div>
                                        <div class="flex items-center">
                                            <div class="flex space-x-1 mr-3">
                                                @for($i = 1; $i <= 10; $i++)
                                                    <div class="w-4 h-4 rounded {{ $i <= $merit['current_level'] ? 'bg-green-500' : 'bg-gray-700' }}"></div>
                                                @endfor
                                            </div>
                                            <span class="text-sm text-green-400">{{ $merit['current_bonus'] }}</span>
                                        </div>
                                    </div>

                                    <div class="bg-gray-900/50 rounded p-3">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-300">Planned</span>
                                            <span class="text-sm text-gray-400">{{ $merit['planned_level'] }}/10</span>
                                        </div>
                                        <div class="flex items-center">
                                            <button 
                                                type="button"
                                                onclick="updateMerit('{{ $merit['name'] }}', -1)"
                                                class="w-6 h-6 flex items-center justify-center bg-gray-700 hover:bg-gray-600 rounded text-white text-sm mr-2"
                                                {{ $merit['planned_level'] <= 0 ? 'disabled' : '' }}
                                            >
                                                -
                                            </button>
                                            <div class="flex space-x-1 mr-3">
                                                @for($i = 1; $i <= 10; $i++)
                                                    <div 
                                                        class="w-4 h-4 rounded cursor-pointer transition-colors {{ $i <= $merit['planned_level'] ? ($merit['has_changes'] ? 'bg-purple-500' : 'bg-green-500') : 'bg-gray-700 hover:bg-gray-600' }}"
                                                        onclick="updateMerit('{{ $merit['name'] }}', {{ $i }})"
                                                    ></div>
                                                @endfor
                                            </div>
                                            <button 
                                                type="button"
                                                onclick="updateMerit('{{ $merit['name'] }}', 1)"
                                                class="w-6 h-6 flex items-center justify-center bg-gray-700 hover:bg-gray-600 rounded text-white text-sm"
                                                {{ $merit['planned_level'] >= 10 ? 'disabled' : '' }}
                                            >
                                                +
                                            </button>
                                        </div>
                                        <div class="flex items-center justify-between mt-2">
                                            <span class="text-sm text-purple-400">{{ $merit['planned_bonus'] }}</span>
                                            <span class="text-sm {{ $merit['cost_to_plan'] > 0 ? 'text-yellow-400' : 'text-gray-500' }}">
                                                @if($merit['cost_to_plan'] > 0)
                                                    Need: +{{ $merit['cost_to_plan'] }} pts
                                                @else
                                                    No additional cost
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    @endif
</div>

@push('scripts')
<script>
function updateMerit(meritName, change) {
    fetch('{{ route('merits.update') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            merit_name: meritName,
            planned_level: change
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            return;
        }
        location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update merit');
    });
}
</script>
@endpush
@endsection
