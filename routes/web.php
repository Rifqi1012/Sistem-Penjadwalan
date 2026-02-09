<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\OrderController;
use App\Http\Controllers\ScheduleController;


Route::get('/dashboard', [ScheduleController::class, 'index'])->name('dashboard');

Route::post('/schedule/run', [ScheduleController::class, 'run'])->name('schedule.run');
Route::post('/schedule/start', [ScheduleController::class, 'startProduction'])->name('schedule.start');

Route::post('/chunks/{chunk}/done', [ScheduleController::class, 'completeChunk'])->name('chunks.done');

Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::get('/history', [OrderController::class, 'history'])->name('history');

Route::post('/units/{unit}/toggle', [ScheduleController::class, 'toggleUnit'])->name('units.toggle');

Route::post('/schedule/complete-day', [ScheduleController::class, 'completeDay'])
    ->name('schedule.completeDay');
