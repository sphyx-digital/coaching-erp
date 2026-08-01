<?php

use App\Livewire\Branches\BranchManager;
use App\Livewire\Enquiries\EnquiryManager;
use App\Livewire\Sessions\SessionManager;
use App\Livewire\Settings\SettingsManager;
use App\Livewire\Staff\StaffManager;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home'))->name('home');

// Lightweight health endpoint for monitoring (Phase 17 extends this).
Route::get('/up', fn () => response()->json(['status' => 'ok', 'phase' => 3]))->name('health');

// Authenticated back office (modules light up phase by phase).
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    Route::get('/enquiries', EnquiryManager::class)->name('enquiries');

    Route::get('/settings', SettingsManager::class)->name('settings');
    Route::get('/branches', BranchManager::class)->name('branches');
    Route::get('/sessions', SessionManager::class)->name('sessions');
    Route::get('/staff', StaffManager::class)->name('staff');

    Route::view('/ui', 'ui.gallery')->name('ui.gallery');
});

require __DIR__.'/auth.php';
