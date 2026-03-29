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
        $this->seedTrainingPrograms();
    }

    private function seedTrainingPrograms(): void
    {
        try {
            $count = DB::table('training_programs')->count();
            if ($count === 0) {
                $programs = [
                    ['name' => 'All-Rounder', 'str_percent' => 25, 'def_percent' => 25, 'spd_percent' => 25, 'dex_percent' => 25, 'is_custom' => false, 'created_at' => now(), 'updated_at' => now()],
                    ['name' => 'Strength Focus', 'str_percent' => 40, 'def_percent' => 20, 'spd_percent' => 20, 'dex_percent' => 20, 'is_custom' => false, 'created_at' => now(), 'updated_at' => now()],
                    ['name' => 'Defense Focus', 'str_percent' => 20, 'def_percent' => 40, 'spd_percent' => 20, 'dex_percent' => 20, 'is_custom' => false, 'created_at' => now(), 'updated_at' => now()],
                    ['name' => 'Speed Focus', 'str_percent' => 20, 'def_percent' => 20, 'spd_percent' => 40, 'dex_percent' => 20, 'is_custom' => false, 'created_at' => now(), 'updated_at' => now()],
                    ['name' => 'Dexterity Focus', 'str_percent' => 20, 'def_percent' => 20, 'spd_percent' => 20, 'dex_percent' => 40, 'is_custom' => false, 'created_at' => now(), 'updated_at' => now()],
                    ['name' => 'STR/DEF Build', 'str_percent' => 35, 'def_percent' => 35, 'spd_percent' => 15, 'dex_percent' => 15, 'is_custom' => false, 'created_at' => now(), 'updated_at' => now()],
                    ['name' => 'SPD/DEX Build', 'str_percent' => 15, 'def_percent' => 15, 'spd_percent' => 35, 'dex_percent' => 35, 'is_custom' => false, 'created_at' => now(), 'updated_at' => now()],
                    ['name' => 'Melee Special', 'str_percent' => 45, 'def_percent' => 25, 'spd_percent' => 15, 'dex_percent' => 15, 'is_custom' => false, 'created_at' => now(), 'updated_at' => now()],
                    ['name' => "Hank's Ratio", 'str_percent' => 28, 'def_percent' => 35, 'spd_percent' => 28, 'dex_percent' => 10, 'is_custom' => false, 'created_at' => now(), 'updated_at' => now()],
                    ['name' => "Balder's Ratio", 'str_percent' => 30, 'def_percent' => 30, 'spd_percent' => 20, 'dex_percent' => 20, 'is_custom' => false, 'created_at' => now(), 'updated_at' => now()],
                    ["Duce's Ratio", 'str_percent' => 25, 'def_percent' => 25, 'spd_percent' => 25, 'dex_percent' => 25, 'is_custom' => false, 'created_at' => now(), 'updated_at' => now()],
                    ['name' => 'Custom', 'str_percent' => 25, 'def_percent' => 25, 'spd_percent' => 25, 'dex_percent' => 25, 'is_custom' => true, 'created_at' => now(), 'updated_at' => now()],
                ];
                DB::table('training_programs')->insert($programs);
            }
        } catch (\Exception $e) {
            // Table doesn't exist yet, skip
        }
    }
}
