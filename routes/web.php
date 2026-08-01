<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home'))->name('home');

// Lightweight health endpoint for monitoring (Phase 17 extends this).
Route::get('/up', fn () => response()->json(['status' => 'ok', 'phase' => 0]))->name('health');
