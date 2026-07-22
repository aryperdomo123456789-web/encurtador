<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LinkController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');

Route::middleware(['auth'])->group(function (): void {
    Route::get('/links', [LinkController::class, 'index'])->name('links.index');
    Route::get('/links/create', [LinkController::class, 'create'])->name('links.create');
    Route::post('/links', [LinkController::class, 'store'])->name('links.store');

    Route::get('/domains', [DomainController::class, 'index'])->name('domains.index');
    Route::post('/domains', [DomainController::class, 'store'])->name('domains.store');

    Route::get('/analytics/{shortCode}', [AnalyticsController::class, 'show'])->name('analytics.show');
});
