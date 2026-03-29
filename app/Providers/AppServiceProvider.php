<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Force HTTPS for all URLs
        if (env('APP_ENV') !== 'local') {
            \URL::forceScheme('https');
        }
        
        $this->seedTrainingPrograms();
    }

    private function seedTrainingPrograms(): void
    {
        // Default programs are now hardcoded in GymAssistantController
        // Only seed custom program entry if it doesn't exist
        try {
            $customExists = DB::table('training_programs')->where('is_custom', true)->exists();
            if (!$customExists) {
                DB::table('training_programs')->insert([
                    'name' => 'Custom',
                    'str_percent' => 25,
                    'def_percent' => 25,
                    'spd_percent' => 25,
                    'dex_percent' => 25,
                    'is_custom' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            // Table doesn't exist yet, skip
        }
    }
}
