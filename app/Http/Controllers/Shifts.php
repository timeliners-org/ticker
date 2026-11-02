<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ShiftCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class Shifts extends Controller
{
    public function index(Request $request): Response
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

    public function getDaily(Request $request): JsonResponse
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

    public function getShift(Request $request): JsonResponse
    {
        /**
         * @var User
         */
        $user = auth()->user();

        $request->validate([
            'id' => ['required', 'exists:shifts,id'],
        ]);

        $shift = Shift::query()
            ->with('category:id,name,color')
            ->where('user_id', $user->id)
            ->whereKey($request->input('id'))
            ->firstOrFail();

        return response()->json($shift);
    }

    public function updateShift(Request $request): JsonResponse
    {
        /**
         * @var User
         */
        $user = auth()->user();

        $request->validate([
            'id' => ['required', 'exists:shifts,id'],
            'start_time' => ['required', 'date_format:Y-m-d H:i:s'],
            'end_time' => ['required', 'date_format:Y-m-d H:i:s', 'after:start_time'],
        ]);

        $shift = Shift::query()
            ->where('user_id', $user->id)
            ->whereKey($request->input('id'))
            ->firstOrFail();
        $categoryIds = ShiftCategory::query()
            ->where(function ($query) use ($user) {
                $query->whereNull('owner_id')
                    ->orWhereIn('owner_id', $user->teams()->select('teams.id'));
            })
            ->pluck('id')
            ->push($shift->shift_category_id)
            ->all();

        $request->validate([
            'shift_category_id' => ['required', Rule::in($categoryIds)],
        ]);

        $shift->start_time = $request->input('start_time');
        $shift->end_time = $request->input('end_time');
        $shift->shift_category_id = $request->input('shift_category_id');
        $shift->save();

        return response()->json($shift);
    }

    public function deleteShift(Request $request): JsonResponse
    {
        /**
         * @var User
         */
        $user = auth()->user();

        $request->validate([
            'id' => ['required', 'exists:shifts,id'],
        ]);

        $shift = Shift::query()
            ->where('user_id', $user->id)
            ->whereKey($request->input('id'))
            ->firstOrFail();
        $shift->delete();

        return response()->json(['deleted' => true]);
    }
}
