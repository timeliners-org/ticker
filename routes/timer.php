<?php

use App\Http\Controllers\TimerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::post('timer/start/{category}', [TimerController::class, 'start'])->name('timer.start');
    Route::post('timer/stop', [TimerController::class, 'stop'])->name('timer.stop');
    Route::post('timer/unpause/{category?}', [TimerController::class, 'unpause'])->name('timer.unpause');
    Route::post('timer/pause', [TimerController::class, 'pause'])->name('timer.pause');
    Route::post('timer/update/{category}', [TimerController::class, 'update'])->name('timer.update');
});
