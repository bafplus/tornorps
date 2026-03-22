<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WarApiController;

Route::get('/setup', [SetupController::class, 'index'])->name('setup');
Route::post('/setup', [SetupController::class, 'store']);

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {

    Route::get('/', function () {
        $settings = \App\Models\FactionSettings::first();
        $hasAdmin = \App\Models\User::where('is_admin', true)->exists();

        if (!$settings || !$hasAdmin) {
            return redirect('/setup');
        }

        return redirect('/dashboard');
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

    Route::get('/api/wars/{warId}/live', [WarApiController::class, 'liveData']);
    Route::get('/api/wars/{warId}/attacks', [WarApiController::class, 'attacks']);
    Route::get('/api/wars/{warId}/stats', [DashboardController::class, 'warStats']);

    Route::get('/settings', [SettingsController::class, 'index']);
    Route::put('/settings/password', [SettingsController::class, 'updatePassword']);
    Route::put('/settings/api-key', [SettingsController::class, 'updateApiKey']);
    
    Route::middleware(['admin'])->group(function () {
        Route::get('/admin', [AdminController::class, 'index']);
        Route::put('/admin/settings', [AdminController::class, 'updateFactionSettings']);
        Route::post('/admin/users', [AdminController::class, 'createUser']);
        Route::post('/admin/users/{user}/toggle', [AdminController::class, 'toggleAdmin']);
        Route::delete('/admin/users/{user}', [AdminController::class, 'deleteUser']);
        
        Route::post('/admin/sync/factions', function () {
            \Illuminate\Support\Facades\Artisan::call('torn:sync-faction');
            return back()->with('status', 'Faction sync completed.');
        });
        
        Route::post('/admin/sync/wars', function () {
            \Illuminate\Support\Facades\Artisan::call('torn:sync-wars');
            return back()->with('status', 'War sync completed.');
        });
    });
});
