<?php

namespace App\Http\Controllers;

use App\Models\ShiftCategory;
use App\Services\TimerService;
use Illuminate\Http\Request;

class TimerController extends Controller
{
    public function __construct(
        protected TimerService $timerService,
    ) {}

    public function start(Request $request, ShiftCategory $category)
    {
        $user = $request->user();

        $this->timerService->startShift($user, $category);

        return back();
    }

    public function stop(Request $request)
    {
        $user = $request->user();

        $this->timerService->stopShift($user);

        return back();
    }

    public function unpause(Request $request, ?ShiftCategory $category)
    {
        $user = $request->user();

        $this->timerService->unpauseShift($user, $category);

        return back();
    }

    public function pause(Request $request)
    {
        $user = $request->user();

        $this->timerService->pauseShift($user);

        return back();
    }

    public function update(Request $request, ShiftCategory $category)
    {
        $user = $request->user();

        $this->timerService->updateCategory($user, $category);

        return back();
    }
}
