<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Playground;
use App\Models\Reservation;
use App\Models\User;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class StatisticsController extends Controller
{
    use JsonResponseTrait;

    /**
     * Overview numbers for the admin dashboard - reservation/revenue counts,
     * facility counts and the most-booked playgrounds. "Paid" here means
     * status = approved (matches how the reservations list and the payment
     * summary PDF define a paid reservation).
     */
    public function index(): JsonResponse
    {
        $now = Carbon::now();
        $monthStart = $now->clone()->startOfMonth();
        $paidStatuses = [Reservation::STATUS_APPROVED];

        $statusCounts = Reservation::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $topPlaygrounds = Reservation::query()
            ->whereIn('status', $paidStatuses)
            ->selectRaw('playground_id, count(*) as reservations_count')
            ->groupBy('playground_id')
            ->orderByDesc('reservations_count')
            ->with('playground.area')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'name' => $row->playground?->name,
                'area_name' => $row->playground?->area?->name,
                'reservations' => $row->reservations_count,
            ]);

        return $this->successResponse([
            'reservations' => [
                'total' => Reservation::query()->count(),
                'this_month' => Reservation::query()->where('created_at', '>=', $monthStart)->count(),
                'upcoming' => Reservation::query()
                    ->where('status', Reservation::STATUS_APPROVED)
                    ->where('start_time', '>=', $now)
                    ->count(),
                'by_status' => $statusCounts,
            ],
            'revenue' => [
                'total_eur' => (float)Reservation::query()->whereIn('status', $paidStatuses)->sum('total_price'),
                'this_month_eur' => (float)Reservation::query()
                    ->whereIn('status', $paidStatuses)
                    ->where('created_at', '>=', $monthStart)
                    ->sum('total_price'),
            ],
            'facilities' => [
                'areas' => Area::query()->count(),
                'playgrounds' => Playground::query()->count(),
                'active_playgrounds' => Playground::query()->where('is_active', true)->count(),
            ],
            'users' => [
                'total' => User::query()->count(),
            ],
            'top_playgrounds' => $topPlaygrounds,
        ]);
    }
}
