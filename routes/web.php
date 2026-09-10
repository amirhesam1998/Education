<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FieldSelectionController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SlotController;
use App\Http\Controllers\Admin\StudyProgramController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicAccess\PublicReservationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
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
    Route::post('/reservations/{reservation}/follow-up', [ReservationController::class, 'storeFollowUp'])
        ->middleware('permission:update_reservations')
        ->name('reservations.follow-up.store');
    Route::delete('/reservations/{reservation}/follow-up/{followUp}', [ReservationController::class, 'destroyFollowUp'])
        ->middleware('permission:update_reservations')
        ->name('reservations.follow-up.destroy');
    Route::post('/reservations/{reservation}/regenerate-link', [ReservationController::class, 'regenerateLink'])
        ->middleware('permission:view_student_public_link')
        ->name('reservations.regenerate-link');
    Route::post('/reservations/{reservation}/disable-public-link', [ReservationController::class, 'disablePublicLink'])
        ->middleware('permission:view_student_public_link')
        ->name('reservations.disable-public-link');
    Route::post('/reservations/{reservation}/enable-public-link', [ReservationController::class, 'enablePublicLink'])
        ->middleware('permission:view_student_public_link')
        ->name('reservations.enable-public-link');
    Route::post('/reservations/{reservation}/complete', [ReservationController::class, 'complete'])
        ->middleware('permission:confirm_reservations')
        ->name('reservations.complete');
    Route::post('/reservations/{reservation}/no-show', [ReservationController::class, 'noShow'])
        ->middleware('permission:confirm_reservations')
        ->name('reservations.no-show');
    Route::get('/reservations/{reservation}/receipt', [ReservationController::class, 'receipt'])
        ->middleware('permission:view_prepayment_receipts')
        ->name('reservations.receipt');
    Route::post('/reservations/{reservation}/documents/report-card', [ReservationController::class, 'uploadReportCard'])
        ->middleware('permission:update_reservations')
        ->name('reservations.documents.report-card.store');
    Route::get('/reservations/{reservation}/documents/{document}', [ReservationController::class, 'document'])
        ->middleware('permission:view_reservation_documents')
        ->name('reservations.documents.show');

    Route::post('/reservations/{reservation}/field-selection', [FieldSelectionController::class, 'createPlan'])
        ->middleware('permission:manage_field_selection')
        ->name('reservations.field-selection.store');
    Route::get('/reservations/{reservation}/field-selection', [FieldSelectionController::class, 'show'])
        ->middleware('permission:view_field_selection')
        ->name('reservations.field-selection.show');
    Route::post('/field-selection-plans/{plan}/items', [FieldSelectionController::class, 'addItem'])
        ->middleware('permission:manage_field_selection')
        ->name('field-selection-plans.items.store');
    Route::post('/field-selection-plans/{plan}/items/from-catalog', [FieldSelectionController::class, 'addItemFromCatalog'])
        ->middleware('permission:manage_field_selection')
        ->name('field-selection-plans.items.from-catalog');
    Route::post('/field-selection-plans/{plan}/bulk-update', [FieldSelectionController::class, 'bulkUpdate'])
        ->middleware('permission:manage_field_selection')
        ->name('field-selection-plans.bulk-update');
    Route::put('/field-selection-items/{item}', [FieldSelectionController::class, 'updateItem'])
        ->middleware('permission:manage_field_selection')
        ->name('field-selection-items.update');
    Route::delete('/field-selection-items/{item}', [FieldSelectionController::class, 'deleteItem'])
        ->middleware('permission:manage_field_selection')
        ->name('field-selection-items.destroy');
    Route::post('/field-selection-plans/{plan}/reorder', [FieldSelectionController::class, 'reorder'])
        ->middleware('permission:manage_field_selection')
        ->name('field-selection-plans.reorder');
    Route::post('/field-selection-plans/{plan}/publish', [FieldSelectionController::class, 'publish'])
        ->middleware('permission:manage_field_selection')
        ->name('field-selection-plans.publish');
    Route::post('/field-selection-plans/{plan}/new-version', [FieldSelectionController::class, 'newVersion'])
        ->middleware('permission:manage_field_selection')
        ->name('field-selection-plans.new-version');
    Route::post('/field-selection-plans/{plan}/public-visibility', [FieldSelectionController::class, 'publicVisibility'])
        ->middleware('permission:manage_field_selection')
        ->name('field-selection-plans.public-visibility');
    Route::post('/field-selection-plans/{plan}/show-to-student', [FieldSelectionController::class, 'showToStudent'])
        ->middleware('permission:manage_field_selection')
        ->name('field-selection-plans.show-to-student');
    Route::post('/field-selection-plans/{plan}/hide-from-student', [FieldSelectionController::class, 'hideFromStudent'])
        ->middleware('permission:manage_field_selection')
        ->name('field-selection-plans.hide-from-student');
    Route::get('/field-selection-plans/{plan}/print', [FieldSelectionController::class, 'print'])
        ->middleware('permission:view_field_selection')
        ->name('field-selection-plans.print');
    Route::get('/field-selection/search-fields', [FieldSelectionController::class, 'searchFields'])
        ->middleware('permission:view_field_selection')
        ->name('field-selection.search-fields');
    Route::get('/field-selection/filter-options/cities', [FieldSelectionController::class, 'filterCities'])
        ->middleware('permission:view_field_selection')
        ->name('field-selection.filter-options.cities');

    Route::get('/payments', [PaymentController::class, 'index'])
        ->middleware('permission:view_reservation_payment_info')
        ->name('payments.index');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])
        ->middleware('permission:view_reservation_payment_info')
        ->name('payments.show');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])
        ->middleware('permission:view_prepayment_receipts')
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

    Route::get('/study-programs', [StudyProgramController::class, 'index'])
        ->middleware('permission:view_reports')
        ->name('study-programs.index');
    Route::get('/study-programs/cities', [StudyProgramController::class, 'cities'])
        ->middleware('permission:view_reports')
        ->name('study-programs.cities');
    Route::get('/study-programs/filter-options/cities', [StudyProgramController::class, 'cities'])
        ->middleware('permission:view_reports')
        ->name('study-programs.filter-options.cities');
    Route::get('/study-programs/filter-options/institutions', [StudyProgramController::class, 'institutions'])
        ->middleware('permission:view_reports')
        ->name('study-programs.filter-options.institutions');
    Route::get('/study-programs/filter-options/academic-fields', [StudyProgramController::class, 'academicFields'])
        ->middleware('permission:view_reports')
        ->name('study-programs.filter-options.academic-fields');
    Route::get('/study-programs/reviews', [StudyProgramController::class, 'reviews'])
        ->middleware('permission:view_reports')
        ->name('study-programs.reviews');
    Route::post('/study-programs/reviews/{review}/reject', [StudyProgramController::class, 'rejectReview'])
        ->middleware('permission:view_reports')
        ->name('study-programs.reviews.reject');
    Route::get('/study-programs/imports', [StudyProgramController::class, 'imports'])
        ->middleware('permission:view_reports')
        ->name('study-programs.imports');
    Route::get('/study-programs/imports/{import}/failures', [StudyProgramController::class, 'failures'])
        ->middleware('permission:view_reports')
        ->name('study-programs.failures');
    Route::get('/study-programs/{studyProgram}', [StudyProgramController::class, 'show'])
        ->middleware('permission:view_reports')
        ->name('study-programs.show');
});

Route::get('/reservation/access/{token}', [PublicReservationController::class, 'show'])->name('public.reservations.show');
Route::get('/reservation/access/{token}/field-selection', [PublicReservationController::class, 'fieldSelection'])->name('public.reservations.field-selection.show');
Route::get('/reservation/access/{token}/field-selection/print', [PublicReservationController::class, 'fieldSelectionPrint'])->name('public.reservations.field-selection.print');
Route::get('/reservation/access/{token}/field-selection/{plan}/print', [PublicReservationController::class, 'printFieldSelection'])->name('public.reservations.field-selection.plan.print');
Route::get('/reservation/access/{token}/field-selection/{plan}', [PublicReservationController::class, 'showFieldSelection'])->name('public.reservations.field-selection.plan.show');
Route::post('/reservation/access/{token}/complete', [PublicReservationController::class, 'complete'])->name('public.reservations.complete');
Route::post('/reservation/access/{token}/upload-receipt', [PublicReservationController::class, 'uploadReceipt'])->name('public.reservations.upload-receipt');
Route::post('/reservation/access/{token}/report-card', [PublicReservationController::class, 'uploadReportCard'])->name('public.reservations.report-card.store');
Route::get('/reservation/access/{token}/report-card/{document}', [PublicReservationController::class, 'reportCard'])->name('public.reservations.report-card.show');
