<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnalyticsRequest;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR18 — the analytics dashboard: booked room nights, room revenue, ADR and a
 * per-hotel breakdown. The heavy lifting is in AnalyticsService.
 */
class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analytics,
    ) {}

    public function index(AnalyticsRequest $request): Response
    {
        [$from, $to] = $this->range($request);

        return Inertia::render('admin/analytics', [
            'from' => $from,
            'to' => $to,
            'summary' => $this->analytics->summary($from, $to),
            'per_hotel' => $this->analytics->perHotel($from, $to),
        ]);
    }

    /** [JSON] partial refresh of the dashboard figures for a new date range. */
    public function refresh(AnalyticsRequest $request): JsonResponse
    {
        [$from, $to] = $this->range($request);

        return response()->json([
            'data' => [
                'from' => $from,
                'to' => $to,
                'summary' => $this->analytics->summary($from, $to),
                'per_hotel' => $this->analytics->perHotel($from, $to),
            ],
        ]);
    }

    /**
     * The requested range, defaulting to the last 90 days.
     *
     * @return array{0: string, 1: string}
     */
    private function range(AnalyticsRequest $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse((string) $request->input('from'))->toDateString()
            : Carbon::now()->subDays(90)->toDateString();

        $to = $request->filled('to')
            ? Carbon::parse((string) $request->input('to'))->toDateString()
            : Carbon::now()->toDateString();

        return [$from, $to];
    }
}
