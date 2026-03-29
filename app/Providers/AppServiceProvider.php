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
        // Force HTTPS only if APP_URL uses https or in production without explicit local setting
        $appUrl = config('app.url', '');
        if (str_starts_with($appUrl, 'https://') || (!$this->app->isLocal() && !str_starts_with($appUrl, 'http://localhost'))) {
            \URL::forceScheme('https');
        }
        
        $this->ensureStorageDirectories();
        $this->seedTrainingPrograms();
    }
    
    private function ensureStorageDirectories(): void
    {
        $directories = [
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
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
