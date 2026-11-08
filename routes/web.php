<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\BulkCsvController;
use App\Http\Controllers\CyberTipController;
use App\Http\Controllers\EmployeeSyncController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\QuoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AdminController::class, 'login'])->name('login');
Route::post('/login', [AdminController::class, 'authenticate'])->name('login.submit');

Route::middleware(['admin.auth'])->group(function () {
    Route::get('/quotes/create', [QuoteController::class, 'create'])->name('quotes.create');
    Route::post('/quotes', [QuoteController::class, 'store'])->name('quotes.store');
    Route::delete('/holidays', [HolidayController::class, 'destroy'])->name('holidays.destroy');
    Route::delete('/quotes', [QuoteController::class, 'destroy'])->name('quotes.destroy');
    Route::delete('/cyber-tips', [CyberTipController::class, 'destroy'])->name('cyber-tips.destroy');
    Route::resource('announcements', AnnouncementController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::get('/admin/profile', [AdminProfileController::class, 'show'])->name('admin.profile');
    Route::post('/admin/profile/sap-credentials', [AdminProfileController::class, 'update'])->name('admin.profile.sap.update');
    Route::get('/admin/notification-count', [AdminController::class, 'notificationCount'])->name('notifications.count');
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
    Route::post('/employees/sync', EmployeeSyncController::class)->name('employees.sync');
    Route::get('/employees/{id}/photo', [AdminController::class, 'photo'])->name('employees.photo');
    Route::post('/employees/{id}/photo', [AdminController::class, 'updatePhoto'])->name('employees.photo.update');
    Route::get('/{module}/csv-template', [BulkCsvController::class, 'template'])->where('module', 'holidays|quotes|cyber-tips')->name('module.csv.template');
    Route::post('/{module}/csv-import', [BulkCsvController::class, 'import'])->where('module', 'holidays|quotes|cyber-tips')->name('module.csv.import');
    Route::get('/{module}', [AdminController::class, 'index'])->name('module.index');
    Route::get('/{module}/{id}', [AdminController::class, 'show'])->name('module.show');
});
