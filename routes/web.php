<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\OrderController;
use App\Http\Controllers\ScheduleController;


Route::get('/', fn() => redirect('/dashboard'));

Route::get('/dashboard', [ScheduleController::class, 'index'])->name('dashboard');
Route::get('/histori', [histori::class, 'index'])->name('histori');

Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');

Route::post('/schedule/run', [ScheduleController::class, 'run'])->name('schedule.run');

Route::post('/units/{unit}/toggle', [ScheduleController::class, 'toggleUnit'])->name('units.toggle');
