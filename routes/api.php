<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Api\AvailabilityController;
use Illuminate\Support\Facades\Route;

/*
 * JSON-only endpoints for partial page updates. The spec keeps the platform on
 * Inertia; only the two routes that return raw JSON live here.
 */

// Feeds the hotel-detail availability calendar without a full page reload (public).
Route::get('hotels/{hotel}/availability', [AvailabilityController::class, 'show'])
    ->name('api.hotels.availability');

// Refreshes the analytics dashboard figures for a new range. Needs the session
// (web) so admin auth works, since this platform is session-based, not tokens.
Route::middleware(['web', 'auth', 'role:admin'])
    ->get('admin/analytics/refresh', [AnalyticsController::class, 'refresh'])
    ->name('api.admin.analytics.refresh');
