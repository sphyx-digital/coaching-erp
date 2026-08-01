<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home'))->name('home');

// Lightweight health endpoint for monitoring (Phase 17 extends this).
Route::get('/up', fn () => response()->json(['status' => 'ok', 'phase' => 2]))->name('health');

// Authenticated back office (modules light up phase by phase).
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
});

require __DIR__.'/auth.php';
