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
use App\Http\Controllers\MeritPlannerController;
use App\Http\Controllers\TravelController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\StocksController;
use App\Http\Controllers\JumpsController;
use App\Http\Controllers\ScriptsController;
use App\Http\Controllers\ToolsController;
use App\Http\Controllers\TargetFinderController;

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
    
    Route::get('/merits', [MeritPlannerController::class, 'index'])->name('merits');
    Route::post('/merits/fetch', [MeritPlannerController::class, 'fetch'])->name('merits.fetch');
    Route::post('/merits/update', [MeritPlannerController::class, 'updatePlanned'])->name('merits.update');
    Route::post('/merits/reset', [MeritPlannerController::class, 'resetPlanned'])->name('merits.reset');
    
    Route::get('/travel', [TravelController::class, 'index']);
    Route::get('/items', [ItemsController::class, 'index']);
    Route::get('/stocks', [StocksController::class, 'index']);
    Route::post('/stocks/update', [StocksController::class, 'update']);
Route::get('/jumps', [JumpsController::class, 'index']);
Route::get('/target-finder', [TargetFinderController::class, 'index']);
Route::post('/target-finder/settings', [TargetFinderController::class, 'saveSettings']);
Route::get('/target-finder/target/{type}', [TargetFinderController::class, 'getTarget']);
Route::get('/target-finder/count/{type}', [TargetFinderController::class, 'getTargetCount']);
Route::get('/target-finder/check-key', [TargetFinderController::class, 'checkKeyStatus']);
Route::post('/target-finder/register-key', [TargetFinderController::class, 'registerKey']);
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

        Route::post('/admin/sync/attacks', function () {
            \Illuminate\Support\Facades\Artisan::call('torn:sync-attacks', ['--force' => true]);
            return back()->with('status', 'War attacks sync completed.');
        });

        Route::post('/admin/sync/stocks', function () {
            \Illuminate\Support\Facades\Artisan::call('torn:sync-stocks');
            return back()->with('status', 'Stocks sync completed.');
        });

        Route::get('/admin/logs', function () {
            $logFile = storage_path('logs/laravel.log');
            $lines = [];
            if (file_exists($logFile)) {
                $handle = fopen($logFile, 'r');
                $buffer = [];
                while (($line = fgets($handle)) !== false) {
                    $buffer[] = $line;
                    if (count($buffer) > 100) {
                        array_shift($buffer);
                    }
                }
                fclose($handle);
                $lines = array_reverse($buffer);
            }
            return view('admin.logs', ['lines' => $lines]);
        });

        Route::post('/admin/check-updates', [AdminController::class, 'checkForUpdates']);
        Route::post('/admin/upgrade', [AdminController::class, 'upgrade']);
    });
});