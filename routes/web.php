<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SlotController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicAccess\PublicReservationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:view_dashboard')
        ->name('dashboard');

    Route::resource('slots', SlotController::class)
        ->middlewareFor('index', 'permission:view_slots')
        ->middlewareFor('show', 'permission:view_slots')
        ->middlewareFor(['create', 'store'], 'permission:create_slots')
        ->middlewareFor(['edit', 'update'], 'permission:update_slots')
        ->middlewareFor('destroy', 'permission:delete_slots');

    Route::resource('reservations', ReservationController::class)
        ->except(['destroy'])
        ->middlewareFor(['index', 'show'], 'permission:view_reservations')
        ->middlewareFor(['create', 'store'], 'permission:create_reservations')
        ->middlewareFor(['edit', 'update'], 'permission:update_reservations');
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])
        ->middleware('permission:cancel_reservations')
        ->name('reservations.cancel');
    Route::post('/reservations/{reservation}/change-slot', [ReservationController::class, 'changeSlot'])
        ->middleware('permission:change_reservation_slot')
        ->name('reservations.change-slot');
    Route::post('/reservations/{reservation}/regenerate-link', [ReservationController::class, 'regenerateLink'])
        ->middleware('permission:update_reservations')
        ->name('reservations.regenerate-link');
    Route::post('/reservations/{reservation}/complete', [ReservationController::class, 'complete'])
        ->middleware('permission:confirm_reservations')
        ->name('reservations.complete');
    Route::post('/reservations/{reservation}/no-show', [ReservationController::class, 'noShow'])
        ->middleware('permission:confirm_reservations')
        ->name('reservations.no-show');
    Route::get('/reservations/{reservation}/receipt', [ReservationController::class, 'receipt'])
        ->middleware('permission:view_reservations')
        ->name('reservations.receipt');
    Route::post('/reservations/{reservation}/documents/report-card', [ReservationController::class, 'uploadReportCard'])
        ->middleware('permission:update_reservations')
        ->name('reservations.documents.report-card.store');
    Route::get('/reservations/{reservation}/documents/{document}', [ReservationController::class, 'document'])
        ->middleware('permission:view_reservations')
        ->name('reservations.documents.show');

    Route::get('/payments', [PaymentController::class, 'index'])
        ->middleware('permission:view_payments')
        ->name('payments.index');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])
        ->middleware('permission:view_payments')
        ->name('payments.show');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])
        ->middleware('permission:view_payments')
        ->name('payments.receipt');
    Route::post('/payments/{payment}/approve', [PaymentController::class, 'approve'])
        ->middleware('permission:approve_payments')
        ->name('payments.approve');
    Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject'])
        ->middleware('permission:reject_payments')
        ->name('payments.reject');

    Route::resource('users', UserController::class)
        ->except(['show'])
        ->middleware('permission:manage_users');

    Route::resource('roles', RoleController::class)
        ->except(['show'])
        ->middleware('permission:manage_roles');

    Route::get('/settings', [SettingController::class, 'edit'])
        ->middleware('permission:manage_settings')
        ->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])
        ->middleware('permission:manage_settings')
        ->name('settings.update');
});

Route::get('/reservation/access/{token}', [PublicReservationController::class, 'show'])->name('public.reservations.show');
Route::post('/reservation/access/{token}/complete', [PublicReservationController::class, 'complete'])->name('public.reservations.complete');
Route::post('/reservation/access/{token}/upload-receipt', [PublicReservationController::class, 'uploadReceipt'])->name('public.reservations.upload-receipt');
Route::post('/reservation/access/{token}/report-card', [PublicReservationController::class, 'uploadReportCard'])->name('public.reservations.report-card.store');
Route::get('/reservation/access/{token}/report-card/{document}', [PublicReservationController::class, 'reportCard'])->name('public.reservations.report-card.show');
