<?php

namespace App\Providers;

use App\Data\TimerStatus;
use App\Models\Shift;
use App\Models\ShiftCategory;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use LogicException;

class TimerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void {}

    /**
     * Bootstrap services.
     */
    public function boot(): void {}

    public function getUserStatus(User $user): ?TimerStatus
    {
        $data = Cache::get(TimerStatus::cacheKey($user->getKey()));

        return $data === null ? null : TimerStatus::fromArray($data);
    }

    public function startShift(User $user, ShiftCategory $shiftCategory): bool
    {
        return $this->putStatus($user, new TimerStatus(now(), $shiftCategory->getKey()));
    }

    public function pauseShift(User $user): bool
    {
        $status = $this->requireStatus($user, 'A shift should be started before it can be paused');

        if ($status->isPaused()) {
            throw new LogicException('Can not pause a shift, that is already paused');
        }

        return $this->putStatus($user, $status->withPausedAt(now()));
    }

    public function unpauseShift(User $user, ?ShiftCategory $shiftCategory): bool
    {
        $status = $this->requireStatus($user, 'A shift should be started before it can be unpaused');

        if (! $status->isPaused()) {
            throw new LogicException('A shift should be paused before it can be unpaused');
        }

        $time = now();

        // Create Pause & Shift records and link them
        $break = Shift::create([
            'user_id' => $user->getKey(),
            'shift_category_id' => $this->breakCategory()->getKey(),
            'start_time' => $status->startedAt,
            'end_time' => $status->pausedAt,
        ]);

        $shift = Shift::create([
            'user_id' => $user->getKey(),
            'shift_category_id' => $status->shiftCategoryId,
            'start_time' => $status->startedAt,
            'end_time' => $status->pausedAt,
            'unpaused_at' => $time,
            'connected_shift_id' => $break->getKey(),
        ]);

        if ($status->updateTargetSet()) {
            $this->updateShift($user, $shift->getKey(), $status->updateShiftId);
        }

        return $this->putStatus($user, new TimerStatus($time, $shiftCategory?->getKey() ?? $status->shiftCategoryId));
    }

    public function updateCategory(User $user, ShiftCategory $shiftCategory): bool
    {
        $status = $this->requireStatus($user, 'A shift should be started before its category can be updated');

        if ($status->isPaused()) {
            throw new LogicException('Can not update the category of a paused shift');
        }

        $time = now();

        $shift = Shift::create([
            'user_id' => $user->getKey(),
            'shift_category_id' => $status->shiftCategoryId,
            'start_time' => $status->startedAt,
            'end_time' => $time,
        ]);

        return $this->putStatus($user, new TimerStatus($time, $shiftCategory->getKey(), updateShiftId: $shift->getKey()));
    }

    public function stopShift(User $user): bool
    {
        $status = $this->requireStatus($user, 'A shift should be started before it can be stopped');

        $time = now();

        $shift = Shift::create([
            'user_id' => $user->getKey(),
            'shift_category_id' => $status->shiftCategoryId,
            'start_time' => $status->startedAt,
            'end_time' => $status->isPaused() ? $status->pausedAt : $time, // If User stops shift while being in a break, drop the break completely
        ]);

        if ($status->updateTargetSet()) {
            $this->updateShift($user, $shift->getKey(), $status->updateShiftId);
        }

        return $this->putStatus($user, new TimerStatus($time, $status->shiftCategoryId, updateShiftId: $shift->getKey()));
    }

    // Created lazily so booting the app does not depend on the database schema
    private function breakCategory(): ShiftCategory
    {
        return ShiftCategory::firstOrCreate(['name' => 'Break', 'owner_id' => null], ['color' => '#000000']);
    }

    private function putStatus(User $user, TimerStatus $status): bool
    {
        return Cache::put(TimerStatus::cacheKey($user->getKey()), $status->toArray());
    }

    private function requireStatus(User $user, string $message): TimerStatus
    {
        return $this->getUserStatus($user) ?? throw new LogicException($message);
    }

    /**
     * Update Target Shift (The Shift before the new one) to point to the new Shift
     *
     * @param  int  $shift_id  Shift after Target
     * @param  int  $target_id  Gets updated
     * @return void
     *
     * @throws LogicException
     */
    private function updateShift(User $user, int $shift_id, int $target_id)
    {
        $target = Shift::find($target_id);
        if (! $target) {
            throw new LogicException('Shift not found');
        }

        $target->connected_shift_id = $shift_id;
        $target->save();
    }
}
