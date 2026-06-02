<?php

use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Owner\OwnerListingPhotoController;
use App\Http\Controllers\Owner\OwnerListingController;
use App\Http\Controllers\Admin\AdminBoardingHouseController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOwnerController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\OwnerAuthController;
use App\Http\Controllers\Owner\OwnerDashboardController;
use App\Http\Controllers\Owner\OwnerReservationController;
use App\Http\Controllers\Owner\OwnerSettingsController;
use App\Http\Controllers\Public\PublicBoardingHouseController;
use App\Http\Controllers\Public\PublicMapController;
use App\Http\Controllers\Public\PublicReservationController;
use App\Http\Controllers\Public\PublicReservationTrackingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Public/Home');
})->name('home');

Route::get('/map', PublicMapController::class)
    ->name('map');

Route::get('/boarding-houses/{boardingHouse:slug}', [PublicBoardingHouseController::class, 'show'])
    ->name('boarding-houses.show');

Route::post('/boarding-houses/{boardingHouse:slug}/reservations', [PublicReservationController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('boarding-houses.reservations.store');

Route::get('/track-reservation', [PublicReservationTrackingController::class, 'index'])
    ->name('track-reservation');

Route::post('/track-reservation', [PublicReservationTrackingController::class, 'search'])
    ->middleware('throttle:10,10')
    ->name('track-reservation.search');

/*
|--------------------------------------------------------------------------
| Owner Authentication Routes
|--------------------------------------------------------------------------
*/

Route::get('/owner/login', [OwnerAuthController::class, 'create'])
    ->name('owner.login');

Route::post('/owner/login', [OwnerAuthController::class, 'store'])
    ->middleware('guest')
    ->name('owner.login.store');

Route::post('/owner/logout', [OwnerAuthController::class, 'destroy'])
    ->middleware(['auth', 'role:owner'])
    ->name('owner.logout');

/*
|--------------------------------------------------------------------------
| Owner Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:owner'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {
        Route::get('/dashboard', OwnerDashboardController::class)
            ->name('dashboard');

        Route::get('/reservations', [OwnerReservationController::class, 'index'])
            ->name('reservations.index');

        Route::get('/listing', [OwnerListingController::class, 'edit'])
            ->name('listing.edit');

        Route::put('/listing', [OwnerListingController::class, 'update'])
            ->name('listing.update');

        Route::post('/listing/photos', [OwnerListingPhotoController::class, 'store'])
            ->name('listing.photos.store');

        Route::post('/listing/photos/{photo}/primary', [OwnerListingPhotoController::class, 'setPrimary'])
            ->name('listing.photos.primary');

        Route::delete('/listing/photos/{photo}', [OwnerListingPhotoController::class, 'destroy'])
            ->name('listing.photos.destroy');

        Route::post('/reservations/{reservation}/approve', [OwnerReservationController::class, 'approve'])
            ->name('reservations.approve');

        Route::post('/reservations/{reservation}/reject', [OwnerReservationController::class, 'reject'])
            ->name('reservations.reject');

        Route::post('/reservations/{reservation}/archive', [OwnerReservationController::class, 'archive'])
            ->name('reservations.archive');

        // Added Settings Routes
        Route::get('/settings', [OwnerSettingsController::class, 'edit'])
            ->name('settings.edit');

        Route::put('/settings/password', [OwnerSettingsController::class, 'updatePassword'])
            ->name('settings.update-password');
    });

/*
|--------------------------------------------------------------------------
| Super Admin Authentication Routes
|--------------------------------------------------------------------------
*/

Route::get('/admin/login', [AdminAuthController::class, 'create'])
    ->name('admin.login');

Route::post('/admin/login', [AdminAuthController::class, 'store'])
    ->middleware('guest')
    ->name('admin.login.store');

Route::post('/admin/logout', [AdminAuthController::class, 'destroy'])
    ->middleware(['auth', 'role:super_admin'])
    ->name('admin.logout');

/*
|--------------------------------------------------------------------------
| Super Admin Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:super_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)
            ->name('dashboard');

        Route::get('/reports', [AdminReportController::class, 'index'])
            ->name('reports.index');

        Route::get('/activity-logs', [AdminActivityLogController::class, 'index'])
            ->name('activity-logs.index');

        Route::get('/owners', [AdminOwnerController::class, 'index'])
            ->name('owners.index');

        Route::post('/owners', [AdminOwnerController::class, 'store'])
            ->name('owners.store');

        Route::post('/owners/{owner}/toggle-status', [AdminOwnerController::class, 'toggleStatus'])
            ->name('owners.toggle-status');

        Route::get('/boarding-houses', [AdminBoardingHouseController::class, 'index'])
            ->name('boarding-houses.index');

        Route::post('/boarding-houses', [AdminBoardingHouseController::class, 'store'])
            ->name('boarding-houses.store');

        Route::put('/boarding-houses/{boardingHouse}', [AdminBoardingHouseController::class, 'update'])
            ->name('boarding-houses.update');

        Route::post('/boarding-houses/{boardingHouse}/approve', [AdminBoardingHouseController::class, 'approve'])
            ->name('boarding-houses.approve');

        Route::post('/boarding-houses/{boardingHouse}/reject', [AdminBoardingHouseController::class, 'reject'])
            ->name('boarding-houses.reject');

        Route::post('/boarding-houses/{boardingHouse}/deactivate', [AdminBoardingHouseController::class, 'deactivate'])
            ->name('boarding-houses.deactivate');

        Route::post('/boarding-houses/{boardingHouse}/reactivate', [AdminBoardingHouseController::class, 'reactivate'])
            ->name('boarding-houses.reactivate');
    });