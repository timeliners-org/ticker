<?php

use App\Http\Controllers\Dashboard;
use App\Http\Controllers\Teams;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [Dashboard::class, 'index'])->name('dashboard');

    Route::get('teams', [Teams::class, 'index'])->name('teams.index');
    Route::post('teams', [Teams::class, 'create'])->name('teams.create');
    Route::get('teams/{teamId}', [Teams::class, 'view'])->name('teams.view');
    Route::post('teams/{teamId}', [Teams::class, 'update'])->name('teams.update');
    Route::delete('teams/{teamId}', [Teams::class, 'destroy'])->name('teams.destroy');
    Route::post('teams/{teamId}/leave', [Teams::class, 'leave'])->name('teams.leave');
    Route::post('teams/{teamId}/categories', [Teams::class, 'addCategory'])->name('teams.addCategory');
    Route::put('teams/{teamId}/categories/{id}', [Teams::class, 'updateCategory'])->name('teams.updateCategory');
    Route::delete('teams/{teamId}/categories/{id}', [Teams::class, 'removeCategory'])->name('teams.removeCategory');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/timer.php';
