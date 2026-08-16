<?php

namespace App\Services;

use App\Models\ReservationNight;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * The three headline metrics for the dashboard (6.3), computed over
     * reservation_nights whose stay_date falls in [from, to] and whose parent
     * reservation is confirmed or completed.
     *
     * @return array{room_nights: int, room_revenue: float, average_daily_rate: float|null}
     */
    public function summary(string $from, string $to): array
    {
        $query = ReservationNight::query()
            ->join('reservations', 'reservations.id', '=', 'reservation_nights.reservation_id')
            ->whereIn('reservations.status', ['confirmed', 'completed'])
            ->whereBetween('reservation_nights.stay_date', [$from, $to]);

        $roomNights = (clone $query)->count();
        $roomRevenue = (float) (clone $query)->sum('reservation_nights.rate');

        return [
            'room_nights' => $roomNights,
            'room_revenue' => round($roomRevenue, 2),
            // ADR is revenue / nights. Return null (not zero) when there are no
            // room nights, so the dashboard never divides by zero (6.3).
            'average_daily_rate' => $roomNights > 0 ? round($roomRevenue / $roomNights, 2) : null,
        ];
    }

    /**
     * The same metrics broken down per hotel, for the dashboard's table.
     *
     * @return array<int, array{hotel_id: int, hotel: string, room_nights: int, room_revenue: float, average_daily_rate: float|null}>
     */
    public function perHotel(string $from, string $to): array
    {
        $rows = DB::table('reservation_nights')
            ->join('reservations', 'reservations.id', '=', 'reservation_nights.reservation_id')
            ->join('hotels', 'hotels.id', '=', 'reservations.hotel_id')
            ->whereIn('reservations.status', ['confirmed', 'completed'])
            ->whereBetween('reservation_nights.stay_date', [$from, $to])
            ->groupBy('hotels.id', 'hotels.name')
            ->select('hotels.id as hotel_id', 'hotels.name as hotel_name')
            ->selectRaw('COUNT(*) as room_nights')
            ->selectRaw('SUM(reservation_nights.rate) as room_revenue')
            ->orderByRaw('SUM(reservation_nights.rate) desc')
            ->get();

        return $rows->map(function (\stdClass $row): array {
            $nights = (int) $row->room_nights;
            $revenue = (float) $row->room_revenue;

            return [
                'hotel_id' => (int) $row->hotel_id,
                'hotel' => $row->hotel_name,
                'room_nights' => $nights,
                'room_revenue' => round($revenue, 2),
                'average_daily_rate' => $nights > 0 ? round($revenue / $nights, 2) : null,
            ];
        })->all();
    }
}
