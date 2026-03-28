@extends('layouts.app')

@section('title', 'Stocks - TornOps')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const thead = document.querySelector('thead');
    const tbody = document.querySelector('tbody');
    let sortCol = 'market_cap';
    let sortDir = 'desc';

    thead.addEventListener('click', function(e) {
        const th = e.target.closest('th[data-sort]');
        if (!th) return;
        
        const col = th.dataset.sort;
        if (sortCol === col) {
            sortDir = sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            sortCol = col;
            sortDir = 'desc';
        }
        
        thead.querySelectorAll('th').forEach(h => {
            h.dataset.dir = h.dataset.sort === sortCol ? sortDir : (h.dataset.sort === 'name' ? 'asc' : 'desc');
            h.querySelector('.sort-icon').textContent = h.dataset.dir === 'asc' ? '↑' : '↓';
        });
        
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort((a, b) => {
            let aVal = a.dataset[col] || '0';
            let bVal = b.dataset[col] || '0';
            
            if (['price', 'market_cap', 'investors', 'shares', 'id'].includes(col)) {
                aVal = parseFloat(aVal.replace(/[^0-9.-]/g, '')) || 0;
                bVal = parseFloat(bVal.replace(/[^0-9.-]/g, '')) || 0;
                return sortDir === 'asc' ? aVal - bVal : bVal - aVal;
            }
            
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
            return sortDir === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
        });
        
        rows.forEach(row => tbody.appendChild(row));
    });
});
</script>
@endpush

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Stock Market</h1>
            <p class="text-gray-400">Current market values from Torn</p>
        </div>
        <form action="/stocks/update" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Refresh Data
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-900/50 border border-green-700 rounded-lg text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if($error)
        <div class="mb-4 p-4 bg-red-900/50 border border-red-700 rounded-lg text-red-400">
            {{ $error }}
        </div>
    @endif

    @if(empty($stocks))
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-8 text-center">
            <p class="text-gray-400">No stock data available. Click refresh to load.</p>
        </div>
    @else
        <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-700">
                        <tr class="text-left text-gray-400 text-sm">
                            <th class="p-3 cursor-pointer hover:text-white" data-sort="id" data-dir="asc">ID <span class="sort-icon">↑</span></th>
                            <th class="p-3 cursor-pointer hover:text-white" data-sort="name" data-dir="asc">Stock <span class="sort-icon">↑</span></th>
                            <th class="p-3 text-right cursor-pointer hover:text-white" data-sort="price" data-dir="desc">Price <span class="sort-icon">↓</span></th>
                            <th class="p-3 text-right cursor-pointer hover:text-white" data-sort="investors" data-dir="desc">Investors <span class="sort-icon">↓</span></th>
                            <th class="p-3">Bonus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @foreach($stocks as $stock)
                        <tr class="hover:bg-gray-700/30" data-id="{{ $stock['id'] }}" data-name="{{ strtolower($stock['name']) }}" data-price="{{ $stock['price'] }}" data-investors="{{ $stock['investors'] }}">
                            <td class="p-3 font-mono text-gray-400">{{ $stock['id'] }}</td>
                            <td class="p-3">
                                <div class="flex items-center gap-2">
                                    @if($stock['logo'])
                                        <img src="{{ $stock['logo'] }}" class="w-6 h-6" alt="">
                                    @endif
                                    <div>
                                        <div class="font-medium">{{ $stock['name'] }}</div>
                                        <div class="text-gray-500 text-sm">{{ $stock['acronym'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 text-right font-mono">${{ number_format($stock['price'], 2) }}</td>
                            <td class="p-3 text-right font-mono text-gray-400">{{ number_format($stock['investors']) }}</td>
                            <td class="p-3 text-xs">
                                @if($stock['bonus_text'])
                                    <div class="@if($stock['bonus_passive']) text-green-400 bg-green-900/30 @else text-blue-400 bg-blue-900/30 @endif px-2 py-1 rounded">
                                        @if($stock['bonus_passive'])<span class="font-semibold">PASSIVE</span> @endif
                                        {{ number_format($stock['bonus_requirement']) }} → {{ $stock['bonus_payout'] }}
                                        @switch($stock['bonus_frequency'])
                                            @case(1) <span class="text-gray-400">(daily)</span> @break
                                            @case(7) <span class="text-gray-400">(weekly)</span> @break
                                            @case(14) <span class="text-gray-400">(bi-weekly)</span> @break
                                            @case(31) <span class="text-gray-400">(monthly)</span> @break
                                            @case(91) <span class="text-gray-400">(quarterly)</span> @break
                                        @endswitch
                                    </div>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
