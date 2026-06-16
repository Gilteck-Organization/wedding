<?php

use App\Http\Controllers\AccessCardController;
use App\Http\Controllers\Admin\AccessNameController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\RsvpAdminController;
use App\Http\Controllers\Admin\WhatsappAdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Webhook\WhatsappWebhookController;
use App\Http\Controllers\Wedding\RsvpController;
use App\Http\Controllers\WeddingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WeddingController::class, 'index'])->name('wedding.home');
Route::view('/privacy', 'wedding.privacy')->name('wedding.privacy');
Route::view('/terms', 'wedding.terms')->name('wedding.terms');

Route::post('/rsvp', [RsvpController::class, 'store'])->name('rsvp.submit');
Route::get('/rsvp', fn () => redirect('/#rsvp'))->name('rsvp.form');
Route::get('/rsvp/phone-availability', [RsvpController::class, 'phoneAvailability'])
    ->middleware('throttle:60,1')
    ->name('rsvp.phone-availability');
Route::get('/rsvp/confirmation', fn () => redirect('/#rsvp'))->name('rsvp.confirmation');

Route::get('/webhook/whatsapp', [WhatsappWebhookController::class, 'verify'])->name('webhook.whatsapp.verify');
Route::post('/webhook/whatsapp', [WhatsappWebhookController::class, 'handle'])->name('webhook.whatsapp.handle');

Route::get('/access-card/{guest}/image.jpg', [AccessCardController::class, 'image'])
    ->where('guest', '[a-z]{5}')
    ->name('access-card.image');
Route::get('/access-card/{guest}', [AccessCardController::class, 'show'])
    ->where('guest', '[a-z]{5}')
    ->name('access-card');
Route::get('/access-card/{guest}/verify', [AccessCardController::class, 'verify'])
    ->where('guest', '[a-z]{5}')
    ->name('access-card.verify');
Route::post('/access-card/{guest}/verify', [AccessCardController::class, 'verifySubmit'])
    ->where('guest', '[a-z]{5}')
    ->middleware('throttle:20,1')
    ->name('access-card.verify.submit');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('admin')->group(function (): void {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/profile', [AdminProfileController::class, 'edit'])->name('admin.profile.edit');
    Route::put('/profile', [AdminProfileController::class, 'update'])->name('admin.profile.update');
    Route::get('/rsvps', [RsvpAdminController::class, 'index'])->name('admin.rsvps.index');
    Route::get('/rsvps/create', [RsvpAdminController::class, 'create'])->name('admin.rsvps.create');
    Route::post('/rsvps', [RsvpAdminController::class, 'storeFromAdmin'])->name('admin.rsvps.store');
    Route::post('/rsvps/bulk-approve', [RsvpAdminController::class, 'approveBulk'])->name('admin.rsvps.bulk-approve');
    Route::post('/rsvps/{rsvp}/approve', [RsvpAdminController::class, 'approve'])->name('admin.rsvps.approve');
    Route::post('/rsvps/{rsvp}/mark-attending', [RsvpAdminController::class, 'markAttending'])->name('admin.rsvps.mark-attending');
    Route::post('/rsvps/{rsvp}/revoke-attendance', [RsvpAdminController::class, 'revokeAttendance'])->name('admin.rsvps.revoke-attendance');
    Route::post('/rsvps/{rsvp}/resend-whatsapp', [RsvpAdminController::class, 'resendWhatsapp'])->name('admin.rsvps.resend-whatsapp');
    Route::get('/rsvps/export.csv', [RsvpAdminController::class, 'exportCsv'])->name('admin.rsvps.export.csv');

    Route::get('/whatsapp', [WhatsappAdminController::class, 'index'])->name('admin.whatsapp.index');
    Route::post('/whatsapp/send', [WhatsappAdminController::class, 'send'])->name('admin.whatsapp.send');

    Route::get('/access-names', [AccessNameController::class, 'index'])->name('admin.access-names.index');
    Route::post('/access-names', [AccessNameController::class, 'store'])->name('admin.access-names.store');
    Route::delete('/access-names/{accessName}', [AccessNameController::class, 'destroy'])->name('admin.access-names.destroy');
});
