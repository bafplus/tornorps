@extends('layouts.app')

@section('title', 'Jump Helper - TornOps')

@push('scripts')
<script>
function calculateJumps() {
    const currentHappy = parseInt(document.getElementById('current_happy').value) || 0;
    const maxHappy = parseInt(document.getElementById('max_happy').value) || 0;
    const currentEnergy = parseInt(document.getElementById('current_energy').value) || 0;
    const maxEnergy = parseInt(document.getElementById('max_energy').value) || 0;
    
    const gymLevel = parseInt(document.getElementById('gym_level').value) || 0;
    const totalStats = parseFloat(document.getElementById('total_stats').value) || 0;
    
    // Calculate effective happy for jumps
    // Happy jump uses 250 happy per jump, need 10k+ happy for max gains
    const happyPerJump = 250;
    const maxHappyNeeded = 10000;
    
    // Stats per jump calculation
    // Base gain depends on gym level and total stats
    const baseGainPerJump = Math.max(1, Math.floor(totalStats / 10000000));
    const gymMultiplier = 1 + (gymLevel * 0.01);
    
    // Calculate number of possible jumps
    const happyJumps = Math.floor(currentHappy / happyPerJump);
    const energyJumps = Math.floor(currentEnergy / 100); // 100 energy per gym hit
    
    const possibleJumps = Math.min(happyJumps, energyJumps);
    
    // Estimated stat gain
    const estimatedGain = possibleJumps * baseGainPerJump * gymMultiplier;
    
    document.getElementById('happy_jumps').textContent = happyJumps;
    document.getElementById('energy_jumps').textContent = energyJumps;
    document.getElementById('possible_jumps').textContent = possibleJumps;
    document.getElementById('estimated_gain').textContent = formatNumber(Math.floor(estimatedGain));
    
    // Update recommendations
    const recommendation = document.getElementById('recommendation');
    if (currentHappy < maxHappyNeeded) {
        recommendation.innerHTML = `<span class="text-yellow-400">⚠️ Build happy to ${formatNumber(maxHappyNeeded)} for optimal jumps</span>`;
    } else if (possibleJumps === 0) {
        recommendation.innerHTML = `<span class="text-red-400">❌ No energy or happy available</span>`;
    } else {
        recommendation.innerHTML = `<span class="text-green-400">✓ Ready to jump! ${possibleJumps} jumps available</span>`;
    }
}

function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

document.addEventListener('DOMContentLoaded', function() {
    calculateJumps();
    
    // Add event listeners to all inputs
    ['current_happy', 'max_happy', 'current_energy', 'max_energy', 'gym_level', 'total_stats'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', calculateJumps);
    });
});
</script>
@endpush

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Jump Helper</h1>
        <p class="text-gray-400">Calculate stat gains from happy jumps</p>
    </div>

    @if($error)
        <div class="mb-4 p-4 bg-red-900/50 border border-red-700 rounded-lg text-red-400">
            {{ $error }}
        </div>
    @endif

    @if($stats && $bars)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Current Stats -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h2 class="text-lg font-semibold mb-4 text-blue-400">Your Stats</h2>
            
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-400">Strength</span>
                    <span class="font-mono">{{ number_format($strength) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Defense</span>
                    <span class="font-mono">{{ number_format($defense) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Speed</span>
                    <span class="font-mono">{{ number_format($speed) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Dexterity</span>
                    <span class="font-mono">{{ number_format($dexterity) }}</span>
                </div>
                <div class="flex justify-between border-t border-gray-700 pt-2">
                    <span class="text-white font-semibold">Total Stats</span>
                    <span class="font-mono text-yellow-400">{{ number_format($total_stats) }}</span>
                </div>
            </div>
        </div>

        <!-- Current Bars -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h2 class="text-lg font-semibold mb-4 text-green-400">Current Bars</h2>
            
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-400">Happy</span>
                    <div class="text-right">
                        <span class="font-mono">{{ number_format($current_happy) }}</span>
                        <span class="text-gray-500">/ {{ number_format($max_happy) }}</span>
                    </div>
                </div>
                <div class="w-full bg-gray-700 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ ($current_happy / $max_happy * 100) }}%"></div>
                </div>
                
                <div class="flex justify-between items-center mt-4">
                    <span class="text-gray-400">Energy</span>
                    <div class="text-right">
                        <span class="font-mono">{{ number_format($current_energy) }}</span>
                        <span class="text-gray-500">/ {{ number_format($max_energy) }}</span>
                    </div>
                </div>
                <div class="w-full bg-gray-700 rounded-full h-2">
                    <div class="bg-blue-500 h-2 rounded-full" style="width: {{ ($current_energy / $max_energy * 100) }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calculator -->
    <div class="mt-6 bg-gray-800 rounded-lg border border-gray-700 p-6">
        <h2 class="text-lg font-semibold mb-4 text-purple-400">Jump Calculator</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-gray-400 text-sm mb-1">Current Happy</label>
                <input type="number" id="current_happy" value="{{ $current_happy }}" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
            </div>
            <div>
                <label class="block text-gray-400 text-sm mb-1">Max Happy</label>
                <input type="number" id="max_happy" value="{{ $max_happy }}" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
            </div>
            <div>
                <label class="block text-gray-400 text-sm mb-1">Current Energy</label>
                <input type="number" id="current_energy" value="{{ $current_energy }}" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
            </div>
            <div>
                <label class="block text-gray-400 text-sm mb-1">Max Energy</label>
                <input type="number" id="max_energy" value="{{ $max_energy }}" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
            </div>
            <div>
                <label class="block text-gray-400 text-sm mb-1">Total Stats</label>
                <input type="number" id="total_stats" value="{{ $total_stats }}" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
            </div>
            <div>
                <label class="block text-gray-400 text-sm mb-1">Gym Level</label>
                <input type="number" id="gym_level" value="1" min="0" max="100" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
            </div>
        </div>

        <!-- Results -->
        <div class="bg-gray-900 rounded-lg p-4 border border-gray-700">
            <div id="recommendation" class="mb-4 text-center font-semibold"></div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                <div>
                    <div class="text-2xl font-bold text-blue-400" id="happy_jumps">0</div>
                    <div class="text-gray-500 text-xs">Happy Jumps</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-green-400" id="energy_jumps">0</div>
                    <div class="text-gray-500 text-xs">Energy Jumps</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-yellow-400" id="possible_jumps">0</div>
                    <div class="text-gray-500 text-xs">Possible Jumps</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-purple-400" id="estimated_gain">0</div>
                    <div class="text-gray-500 text-xs">Est. Stat Gain</div>
                </div>
            </div>
        </div>

        <div class="mt-4 p-3 bg-blue-900/30 border border-blue-700 rounded text-sm text-blue-300">
            <strong>Happy Jump Info:</strong> Each jump uses 250 happy. For maximum stat gains, accumulate 10,000+ happy before jumping. Each gym hit uses 100 energy.
        </div>
    </div>
    @endif
</div>
@endsection
