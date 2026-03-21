<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\DashboardController;

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

Route::prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/members', [DashboardController::class, 'members']);
    Route::get('/wars', [DashboardController::class, 'wars']);
    Route::get('/wars/{warId}', [DashboardController::class, 'warDetail']);
});

Route::get('/members', [DashboardController::class, 'members']);
Route::get('/wars', [DashboardController::class, 'wars']);
Route::get('/wars/{warId}', [DashboardController::class, 'warDetail']);

Route::middleware(['auth'])->group(function () {
    Route::get('/settings', function () {
        return view('settings.index');
    });
    
    Route::get('/admin', function () {
        if (!auth()->user()->is_admin) {
            abort(403);
        }
        $settings = \App\Models\FactionSettings::first();
        return view('admin.index', compact('settings'));
    });
});
