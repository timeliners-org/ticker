<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class Shifts
{
    public function index(Request $request)
    {
        /**
         * @var User
         */
        $user = auth()->user();

        $datesWithShifts = $user->shifts()
            ->selectRaw('DATE(start_time) as date')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('date');

        return Inertia::render('Shifts/Index', [
            'datesWithShifts' => $datesWithShifts,
        ]);
    }

    public function getDaily(Request $request)
    {
        /**
         * @var User
         */
        $user = auth()->user();

        $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::parse($request->query('date', Carbon::now()->toDateString()));

        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $shifts = $user->shifts()
            ->with('category:id,name,color')
            ->whereBetween('start_time', [$start, $end])
            ->orderBy('start_time')
            ->get();

        return response()->json($shifts);
    }
}
