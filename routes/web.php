<?php

use App\Http\Controllers\AccessCardController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\RsvpAdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\WeddingController;
use App\Http\Controllers\Wedding\RsvpController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WeddingController::class, 'index'])->name('wedding.home');

Route::redirect('/rsvp', '/#rsvp', 302)->name('rsvp.form');
Route::post('/rsvp', [RsvpController::class, 'store'])->name('rsvp.submit');
Route::redirect('/rsvp/confirmation', '/#rsvp', 302)->name('rsvp.confirmation');

Route::get('/access-card/{guest}', [AccessCardController::class, 'show'])->name('access-card');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('admin')->group(function (): void {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/rsvps', [RsvpAdminController::class, 'index'])->name('admin.rsvps.index');
    Route::post('/rsvps/{rsvp}/approve', [RsvpAdminController::class, 'approve'])->name('admin.rsvps.approve');
    Route::get('/rsvps/export.csv', [RsvpAdminController::class, 'exportCsv'])->name('admin.rsvps.export.csv');
});
