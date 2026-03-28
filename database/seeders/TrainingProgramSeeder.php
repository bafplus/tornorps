<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrainingProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            ['name' => 'All-Rounder', 'str_percent' => 25, 'def_percent' => 25, 'spd_percent' => 25, 'dex_percent' => 25, 'is_custom' => false],
            ['name' => 'STR/DEF Build', 'str_percent' => 35, 'def_percent' => 35, 'spd_percent' => 15, 'dex_percent' => 15, 'is_custom' => false],
            ['name' => 'SPD/DEX Build', 'str_percent' => 15, 'def_percent' => 15, 'spd_percent' => 35, 'dex_percent' => 35, 'is_custom' => false],
            ['name' => 'Melee Special', 'str_percent' => 45, 'def_percent' => 25, 'spd_percent' => 15, 'dex_percent' => 15, 'is_custom' => false],
            ['name' => 'Custom', 'str_percent' => 25, 'def_percent' => 25, 'spd_percent' => 25, 'dex_percent' => 25, 'is_custom' => true],
        ];

        foreach ($programs as $program) {
            DB::table('training_programs')->insert($program);
        }
    }
}
