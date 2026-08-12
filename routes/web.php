<?php

use App\Http\Controllers\Admin\AdjustmentHistoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
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
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
