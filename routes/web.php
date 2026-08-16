<?php

use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\HotelController as AdminHotelController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\Manager\AvailabilityController as ManagerAvailabilityController;
use App\Http\Controllers\Manager\PromotionController as ManagerPromotionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RewardsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ServiceBookingController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

// Search & Availability (public). FR3 search and FR4 hotel detail.
Route::get('search', [SearchController::class, 'index'])->name('search');
Route::get('hotels/{hotel}', [HotelController::class, 'show'])->name('hotels.show');

// Contact form (FR11), public and rate-limited against spam.
Route::inertia('contact', 'contact')->name('contact');
Route::post('enquiries', [EnquiryController::class, 'store'])->middleware('throttle:10,1')->name('enquiries.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    // Reservations (FR6-FR8). A guest may only act on their own bookings.
    Route::get('reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::post('reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::get('reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::post('reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');

    // Payments (Payment module). FR12.
    Route::post('reservations/{reservation}/pay', [PaymentController::class, 'store'])->name('reservations.pay');
    Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');

    // Reviews (FR10).
    Route::post('reservations/{reservation}/review', [ReviewController::class, 'store'])->name('reservations.review');

    // Reward points (FR13).
    Route::get('rewards', [RewardsController::class, 'index'])->name('rewards.index');

    // Ancillary services (FR9).
    Route::post('reservations/{reservation}/services', [ServiceBookingController::class, 'store'])->name('reservations.services.store');
});

// Hotel manager area (FR14, FR15). Admins have full access too.
Route::middleware(['auth', 'role:manager,admin'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('promotions', [ManagerPromotionController::class, 'index'])->name('promotions.index');
    Route::post('promotions', [ManagerPromotionController::class, 'store'])->name('promotions.store');
    Route::delete('promotions/{promotion}', [ManagerPromotionController::class, 'destroy'])->name('promotions.destroy');

    Route::get('availability', [ManagerAvailabilityController::class, 'index'])->name('availability.index');
    Route::put('availability', [ManagerAvailabilityController::class, 'update'])->name('availability.update');
});

// Administrator area (FR16, FR17, FR18). Gated by role, not scattered controller checks.
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('hotels', [AdminHotelController::class, 'index'])->name('hotels.index');
    Route::post('hotels', [AdminHotelController::class, 'store'])->name('hotels.store');
    Route::put('hotels/{hotel}', [AdminHotelController::class, 'update'])->name('hotels.update');
    Route::delete('hotels/{hotel}', [AdminHotelController::class, 'destroy'])->name('hotels.destroy');

    Route::get('reservations', [AdminReservationController::class, 'index'])->name('reservations.index');

    Route::get('analytics', [AdminAnalyticsController::class, 'index'])->name('analytics.index');
});

require __DIR__.'/settings.php';
