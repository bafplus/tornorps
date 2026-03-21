<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SetupController;

Route::get('/setup', [SetupController::class, 'index'])->name('setup');
Route::post('/setup', [SetupController::class, 'store']);

Route::get('/', function () {
    $settings = \App\Models\FactionSettings::first();
    $hasAdmin = \App\Models\User::where('is_admin', true)->exists();

    if (!$settings || !$hasAdmin) {
        return redirect('/setup');
    }

    return view('welcome');
});
