<?php

namespace App\Console\Commands;

use App\Services\TornApiService;
use App\Models\FactionSettings;
use App\Models\StockHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncStocks extends Command
{
    protected $signature = 'torn:sync-stocks';
    protected $description = 'Sync stock prices and save to history';

    public function handle()
    {
        $settings = FactionSettings::first();
        $apiKey = $settings?->torn_api_key;
        
        if (!$apiKey) {
            $this->error('No faction API key found.');
            return 1;
        }

        Cache::forget('stocks_data');
        
        $tornApi = app(TornApiService::class);
        $rawStocks = $tornApi->getStocks($apiKey);
        
        if (!$rawStocks) {
            $this->error('Failed to fetch stock data.');
            return 1;
        }

        $today = now()->toDateString();
        $count = 0;
        
        foreach ($rawStocks as $stock) {
            StockHistory::updateOrCreate(
                ['stock_id' => $stock['id'] ?? 0, 'recorded_at' => $today],
                [
                    'name' => $stock['name'] ?? '',
                    'acronym' => $stock['acronym'] ?? '',
                    'price' => $stock['market']['price'] ?? 0,
                    'investors' => $stock['market']['investors'] ?? 0,
                    'shares' => $stock['market']['shares'] ?? 0,
                    'market_cap' => $stock['market']['cap'] ?? 0,
                ]
            );
            $count++;
        }

        $this->info("Synced {$count} stocks and saved to history.");
        return 0;
    }
}
