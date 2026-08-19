<?php

use App\Http\Controllers\Admin\AdjustmentHistoryController;
use App\Http\Controllers\Admin\DepositController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Market Data API Routes (Public - Read-only)
Route::prefix('api/market')->group(function () {
    Route::get('/ticker', [App\Http\Controllers\Api\MarketController::class, 'ticker'])->name('api.market.ticker');
    Route::get('/tickers', [App\Http\Controllers\Api\MarketController::class, 'tickers'])->name('api.market.tickers');
    Route::get('/klines', [App\Http\Controllers\Api\MarketController::class, 'klines'])->name('api.market.klines');
    Route::get('/websocket', [App\Http\Controllers\Api\MarketController::class, 'websocket'])->name('api.market.websocket');
    Route::get('/health', [App\Http\Controllers\Api\MarketController::class, 'health'])->name('api.market.health');
});

Route::middleware(['auth', 'verified', 'role:user', 'account.active'])->group(function () {
    Route::get('/dashboard', function () {
        return view('users.dashboard');
    })->name('dashboard');
});

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('admin.dashboard');

        Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('admin.users.show');
        Route::post('/users/{user}/freeze', [UserController::class, 'freeze'])->name('admin.users.freeze');
        Route::post('/users/{user}/unfreeze', [UserController::class, 'unfreeze'])->name('admin.users.unfreeze');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('admin.users.reset-password');
        Route::post('/users/{user}/reset-withdrawal-password', [UserController::class, 'resetWithdrawalPassword'])->name('admin.users.reset-withdrawal-password');
        Route::post('/users/{user}/adjust-balance', [UserController::class, 'adjustBalance'])->name('admin.users.adjust-balance');

        Route::get('/adjustment-history', [AdjustmentHistoryController::class, 'index'])->name('admin.adjustment-history.index');

        Route::get('/deposits', [DepositController::class, 'index'])->name('admin.deposits.index');
        Route::get('/deposits/{deposit}', [DepositController::class, 'show'])->name('admin.deposits.show');
        Route::post('/deposits/{deposit}/approve', [DepositController::class, 'approve'])->name('admin.deposits.approve');
        Route::post('/deposits/{deposit}/reject', [DepositController::class, 'reject'])->name('admin.deposits.reject');

        Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings.index');
        Route::post('/settings/dashboard-name', [SettingsController::class, 'updateDashboardName'])->name('admin.settings.dashboard-name');
        Route::post('/settings/profile-photo', [SettingsController::class, 'updateProfilePhoto'])->name('admin.settings.profile-photo');
        Route::post('/settings/binance-api', [SettingsController::class, 'updateBinanceApi'])->name('admin.settings.binance-api');
        Route::delete('/settings/binance-api', [SettingsController::class, 'deleteBinanceApi'])->name('admin.settings.binance-api.delete');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
