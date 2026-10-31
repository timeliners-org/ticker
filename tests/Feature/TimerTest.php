<?php

use App\Models\Shift;
use App\Models\ShiftCategory;
use App\Models\User;
use App\Providers\TimerServiceProvider;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2026-01-01 08:00:00');

    $this->timer = app()->getProvider(TimerServiceProvider::class);
    $this->user = User::factory()->create();
    $this->work = ShiftCategory::factory()->create(['name' => 'Work']);
    $this->other = ShiftCategory::factory()->create(['name' => 'Other']);
});

afterEach(function () {
    Carbon::setTestNow();
});

test('start and stop creates one shift', function () {
    $this->timer->startShift($this->user, $this->work);

    expect($this->timer->getUserStatus($this->user))
        ->startedAt->toEqual(now())
        ->shiftCategoryId->toBe($this->work->id)
        ->isPaused()->toBeFalse();

    Carbon::setTestNow('2026-01-01 09:00:00');
    $this->timer->stopShift($this->user);

    expect(Shift::count())->toBe(1);

    $shift = Shift::first();
    expect($shift->user_id)->toBe($this->user->id)
        ->and($shift->shift_category_id)->toBe($this->work->id)
        ->and($shift->start_time)->toEqual(Carbon::parse('2026-01-01 08:00:00'))
        ->and($shift->end_time)->toEqual(Carbon::parse('2026-01-01 09:00:00'))
        ->and($shift->connected_shift_id)->toBeNull();
});

test('start, pause and stop drops the break', function () {
    $this->timer->startShift($this->user, $this->work);

    Carbon::setTestNow('2026-01-01 09:00:00');
    $this->timer->pauseShift($this->user);
    expect($this->timer->getUserStatus($this->user)->isPaused())->toBeTrue();

    Carbon::setTestNow('2026-01-01 09:30:00');
    $this->timer->stopShift($this->user);

    expect(Shift::count())->toBe(1);

    $shift = Shift::first();
    expect($shift->shift_category_id)->toBe($this->work->id)
        ->and($shift->start_time)->toEqual(Carbon::parse('2026-01-01 08:00:00'))
        ->and($shift->end_time)->toEqual(Carbon::parse('2026-01-01 09:00:00'));
});

test('start, pause, unpause and stop creates work, break and work shifts', function () {
    $this->timer->startShift($this->user, $this->work);

    Carbon::setTestNow('2026-01-01 09:00:00');
    $this->timer->pauseShift($this->user);

    Carbon::setTestNow('2026-01-01 09:30:00');
    $this->timer->unpauseShift($this->user, null);

    expect($this->timer->getUserStatus($this->user))
        ->startedAt->toEqual(Carbon::parse('2026-01-01 09:30:00'))
        ->shiftCategoryId->toBe($this->work->id)
        ->isPaused()->toBeFalse();

    Carbon::setTestNow('2026-01-01 10:00:00');
    $this->timer->stopShift($this->user);

    $shifts = Shift::orderBy('id')->get();
    $break = $shifts->first(fn (Shift $shift) => $shift->category->name === 'Break');
    $work = $shifts->where('shift_category_id', $this->work->id)->values();

    expect($shifts)->toHaveCount(3)
        ->and($break)->not->toBeNull()
        ->and($work)->toHaveCount(2)
        ->and($work[0]->start_time)->toEqual(Carbon::parse('2026-01-01 08:00:00'))
        ->and($work[0]->end_time)->toEqual(Carbon::parse('2026-01-01 09:00:00'))
        ->and($work[0]->connected_shift_id)->toBe($break->id)
        ->and($work[1]->start_time)->toEqual(Carbon::parse('2026-01-01 09:30:00'))
        ->and($work[1]->end_time)->toEqual(Carbon::parse('2026-01-01 10:00:00'));
});

test('start, update category and stop creates two linked shifts', function () {
    $this->timer->startShift($this->user, $this->work);

    Carbon::setTestNow('2026-01-01 09:00:00');
    $this->timer->updateCategory($this->user, $this->other);

    expect($this->timer->getUserStatus($this->user)->shiftCategoryId)->toBe($this->other->id);

    Carbon::setTestNow('2026-01-01 10:00:00');
    $this->timer->stopShift($this->user);

    $shifts = Shift::orderBy('id')->get();

    expect($shifts)->toHaveCount(2)
        ->and($shifts[0]->shift_category_id)->toBe($this->work->id)
        ->and($shifts[0]->end_time)->toEqual(Carbon::parse('2026-01-01 09:00:00'))
        ->and($shifts[0]->connected_shift_id)->toBe($shifts[1]->id)
        ->and($shifts[1]->shift_category_id)->toBe($this->other->id)
        ->and($shifts[1]->start_time)->toEqual(Carbon::parse('2026-01-01 09:00:00'))
        ->and($shifts[1]->end_time)->toEqual(Carbon::parse('2026-01-01 10:00:00'));
});
