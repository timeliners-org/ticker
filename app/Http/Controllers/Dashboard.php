<?php

namespace App\Http\Controllers;

use App\Models\Quota;
use App\Models\Shift;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class Dashboard extends Controller
{
    public function index(Request $request): Response
    {
        /**
         * @var User
         */
        $user = $request->user();

        $shifts = Shift::query()
            ->where('shifts.user_id', $user->id)
            ->whereNotNull('shifts.end_time')
            ->where('shifts.start_time', '>=', now()->subDays(7)->startOfDay())
            ->join('shift_categories', 'shift_categories.id', '=', 'shifts.shift_category_id')
            ->orderBy('shifts.start_time')
            ->get([
                'shifts.id',
                'shifts.shift_category_id',
                'shift_categories.name as category_name',
                'shift_categories.color as category_color',
                'shift_categories.owner_id as category_owner_id',
                'shifts.start_time',
                'shifts.end_time',
            ])
            ->groupBy(fn (Shift $shift) => $shift->start_time->format('Y-m-d'))
            ->map(fn ($day) => $day->map(fn (Shift $shift) => [
                'id' => $shift->id,
                'shift_category_id' => $shift->shift_category_id,
                'category_name' => $shift->getAttribute('category_name'),
                'category_color' => $shift->getAttribute('category_color'),
                // The break category is the only one without an owner.
                'is_break' => $shift->getAttribute('category_owner_id') === null,
                'start_time' => $shift->start_time->toIso8601String(),
                'duration_seconds' => $shift->start_time->diffInSeconds($shift->end_time, true),
            ])->values());

        $todayWorkingTime = Shift::query()
            ->where('user_id', $user->id)
            ->whereNotNull('end_time')
            ->with('category')
            ->whereHas('category', fn ($query) => $query->whereNotNull('owner_id'))
            ->where('start_time', '>=', now()->startOfDay())
            ->get()
            ->reduce(
                fn (float $sum, Shift $shift): float => $sum + (float) $shift->start_time->diffInSeconds($shift->end_time, true),
                0.0,
            );

        $quotas = Quota::query()
            ->where('user_id', $user->id)
            ->get();

        $workView = DB::table('work_view')
            ->where('user_id', $user->id)
            ->groupBy('date')
            ->get([
                'date',
                DB::raw('SUM(total_shift_seconds) as total_shift_seconds'),
            ]);

        $overtime = 0;

        foreach ($quotas as $quota) {
            $workForQuotaDate = $workView->firstWhere('date', $quota->date->format('Y-m-d'));
            if ($workForQuotaDate) {
                $overtime += (int) $workForQuotaDate->total_shift_seconds - (int) $quota->quota;
            }
        }

        return Inertia::render('Dashboard', [
            'shifts' => $shifts,
            'todayWorkingTime' => $todayWorkingTime, // Provided in seconds
            'overtime' => $overtime, // Provided in seconds
        ]);
    }
}
