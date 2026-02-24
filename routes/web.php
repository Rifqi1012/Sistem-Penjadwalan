<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ScheduleController;

// --- Auth (public) ---
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// --- Protected routes ---
Route::middleware('auth')->group(function () {
    Route::get('/', fn() => redirect('/dashboard'));

    Route::get('/dashboard', [ScheduleController::class, 'index'])->name('dashboard');

    Route::post('/schedule/run', [ScheduleController::class, 'run'])->name('schedule.run');
    Route::post('/schedule/start', [ScheduleController::class, 'startProduction'])->name('schedule.start');
    Route::post('/schedule/complete-day', [ScheduleController::class, 'completeDay'])->name('schedule.completeDay');

    Route::post('/chunks/{chunk}/done', [ScheduleController::class, 'completeChunk'])->name('chunks.done');

    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/history', [OrderController::class, 'history'])->name('history');

    Route::post('/units/{unit}/toggle', [ScheduleController::class, 'toggleUnit'])->name('units.toggle');
    Route::post('/units', [ScheduleController::class, 'storeUnit'])->name('units.store');
    Route::delete('/units/{unit}', [ScheduleController::class, 'destroyUnit'])->name('units.destroy');
});
