<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\WarApiController;
use App\Http\Controllers\GymAssistantController;
use App\Http\Controllers\TravelController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\StocksController;
use App\Http\Controllers\JumpsController;
use App\Http\Controllers\ScriptsController;
use App\Http\Controllers\ToolsController;

Route::get('/setup', [SetupController::class, 'index'])->name('setup');
Route::post('/setup', [SetupController::class, 'store']);

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/invite/{token}', [InvitationController::class, 'showInviteForm'])->name('invite');
Route::post('/invite/{token}', [InvitationController::class, 'acceptInvite']);

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
    
    Route::get('/gym', [GymAssistantController::class, 'index']);
    Route::post('/gym/update', [GymAssistantController::class, 'update']);
    Route::post('/gym/program', [GymAssistantController::class, 'selectProgram']);
    
    Route::get('/travel', [TravelController::class, 'index']);
    Route::get('/items', [ItemsController::class, 'index']);
    Route::get('/stocks', [StocksController::class, 'index']);
    Route::get('/jumps', [JumpsController::class, 'index']);
    Route::get('/scripts', [ScriptsController::class, 'index']);
    Route::get('/tools', [ToolsController::class, 'index']);
    
    Route::middleware(['admin'])->group(function () {
        Route::get('/admin', [AdminController::class, 'index']);
        Route::put('/admin/settings', [AdminController::class, 'updateFactionSettings']);
        Route::post('/admin/users', [AdminController::class, 'createUser']);
        Route::post('/admin/users/{user}/regenerate', [AdminController::class, 'regenerateInvite']);
        Route::post('/admin/users/{user}/toggle', [AdminController::class, 'toggleAdmin']);
        Route::delete('/admin/users/{user}', [AdminController::class, 'deleteUser']);
        
        Route::post('/admin/sync/factions', function () {
            \Illuminate\Support\Facades\Artisan::call('torn:sync-faction');
            return back()->with('status', 'Faction sync completed.');
        });
        
        Route::post('/admin/sync/members', function () {
            \Illuminate\Support\Facades\Artisan::call('torn:sync-members');
            return back()->with('status', 'Member sync completed.');
        });
        
        Route::post('/admin/sync/wars', function () {
            \Illuminate\Support\Facades\Artisan::call('torn:sync-wars');
            return back()->with('status', 'War sync completed.');
        });

        Route::post('/admin/sync/active', function () {
            \Illuminate\Support\Facades\Artisan::call('torn:sync-active', ['--force' => true]);
            return back()->with('status', 'Active wars sync completed.');
        });

        Route::post('/admin/check-updates', [AdminController::class, 'checkForUpdates']);
        Route::post('/admin/upgrade', [AdminController::class, 'upgrade']);
    });
});