<?php

use App\Models\Shift;
use App\Models\ShiftCategory;
use App\Models\User;
use App\Services\TimerService;
use Illuminate\Support\Carbon;

function at(string $time): void
{
    Carbon::setTestNow("2026-01-01 {$time}");
}

function timerStatus(User $user)
{
    return TimerService::getUserStatus($user);
}

/**
 * Walks the chain from its head (the earliest shift) via connected_shift_id.
 *
 * @return array<int, Shift>
 */
function shiftChain(User $user): array
{
    $first = Shift::where('user_id', $user->id)->orderBy('start_time')->first();
    $chain = [];

    for ($shift = $first; $shift; $shift = $shift->connectedShift) {
        $chain[] = $shift;
    }

    return $chain;
}

beforeEach(function () {
    at('08:00:00');

    $this->user = User::factory()->create();
    $this->work = ShiftCategory::factory()->create(['name' => 'Work']);
    $this->other = ShiftCategory::factory()->create(['name' => 'Other']);
    $this->actingAs($this->user);
});

afterEach(function () {
    Carbon::setTestNow();
});

test('guests cannot control the timer', function () {
    auth()->logout();

    $this->post(route('timer.start', $this->work))->assertRedirect(route('login'));
    $this->post(route('timer.stop'))->assertRedirect(route('login'));
});

test('start creates a running timer status', function () {
    $this->post(route('timer.start', $this->work))->assertRedirect();

    expect(timerStatus($this->user))
        ->createdAt->toEqual(now())
        ->startedAt->toEqual(now())
        ->shiftCategoryId->toBe($this->work->id)
        ->isPaused()->toBeFalse()
        ->and(Shift::count())->toBe(0);
});

test('start and stop creates one shift and clears the status', function () {
    $this->post(route('timer.start', $this->work));

    at('09:00:00');
    $this->post(route('timer.stop'))->assertRedirect();

    $shift = Shift::sole();

    expect(timerStatus($this->user))->toBeNull()
        ->and($shift->user_id)->toBe($this->user->id)
        ->and($shift->shift_category_id)->toBe($this->work->id)
        ->and($shift->start_time)->toEqual(Carbon::parse('2026-01-01 08:00:00'))
        ->and($shift->end_time)->toEqual(Carbon::parse('2026-01-01 09:00:00'))
        ->and($shift->connected_shift_id)->toBeNull();
});

test('stopping while paused drops the break', function () {
    $this->post(route('timer.start', $this->work));

    at('09:00:00');
    $this->post(route('timer.pause'));
    expect(timerStatus($this->user))
        ->isPaused()->toBeTrue()
        ->createdAt->toEqual(Carbon::parse('2026-01-01 08:00:00'))
        ->startedAt->toEqual(Carbon::parse('2026-01-01 08:00:00'));

    at('09:30:00');
    $this->post(route('timer.stop'));

    $shift = Shift::sole();

    expect($shift->shift_category_id)->toBe($this->work->id)
        ->and($shift->start_time)->toEqual(Carbon::parse('2026-01-01 08:00:00'))
        ->and($shift->end_time)->toEqual(Carbon::parse('2026-01-01 09:00:00'))
        ->and(timerStatus($this->user))->toBeNull();
});

test('pause and unpause chain work, break and work shifts', function () {
    $this->post(route('timer.start', $this->work));

    at('09:00:00');
    $this->post(route('timer.pause'));

    at('09:30:00');
    $this->post(route('timer.unpause'));

    expect(timerStatus($this->user))
        ->createdAt->toEqual(Carbon::parse('2026-01-01 08:00:00'))
        ->startedAt->toEqual(Carbon::parse('2026-01-01 09:30:00'))
        ->shiftCategoryId->toBe($this->work->id)
        ->isPaused()->toBeFalse();

    at('10:00:00');
    $this->post(route('timer.stop'));

    $chain = shiftChain($this->user);

    expect($chain)->toHaveCount(3)
        ->and($chain[0]->category->name)->toBe('Work')
        ->and($chain[0]->start_time)->toEqual(Carbon::parse('2026-01-01 08:00:00'))
        ->and($chain[0]->end_time)->toEqual(Carbon::parse('2026-01-01 09:00:00'))
        ->and($chain[1]->category->name)->toBe('Break')
        ->and($chain[1]->start_time)->toEqual(Carbon::parse('2026-01-01 09:00:00'))
        ->and($chain[1]->end_time)->toEqual(Carbon::parse('2026-01-01 09:30:00'))
        ->and($chain[2]->category->name)->toBe('Work')
        ->and($chain[2]->start_time)->toEqual(Carbon::parse('2026-01-01 09:30:00'))
        ->and($chain[2]->end_time)->toEqual(Carbon::parse('2026-01-01 10:00:00'))
        ->and($chain[2]->connected_shift_id)->toBeNull();
});

test('unpause can switch to another category', function () {
    $this->post(route('timer.start', $this->work));

    at('09:00:00');
    $this->post(route('timer.pause'));

    at('09:30:00');
    $this->post(route('timer.unpause', $this->other));

    expect(timerStatus($this->user)->shiftCategoryId)->toBe($this->other->id);

    at('10:00:00');
    $this->post(route('timer.stop'));

    $chain = shiftChain($this->user);

    expect($chain)->toHaveCount(3)
        ->and($chain[2]->shift_category_id)->toBe($this->other->id);
});

test('updating the category while running links the shifts', function () {
    $this->post(route('timer.start', $this->work));

    at('09:00:00');
    $this->post(route('timer.update', $this->other))->assertRedirect();

    expect(timerStatus($this->user))
        ->shiftCategoryId->toBe($this->other->id)
        ->createdAt->toEqual(Carbon::parse('2026-01-01 08:00:00'))
        ->startedAt->toEqual(Carbon::parse('2026-01-01 09:00:00'));

    at('10:00:00');
    $this->post(route('timer.stop'));

    $chain = shiftChain($this->user);

    expect($chain)->toHaveCount(2)
        ->and($chain[0]->shift_category_id)->toBe($this->work->id)
        ->and($chain[0]->end_time)->toEqual(Carbon::parse('2026-01-01 09:00:00'))
        ->and($chain[1]->shift_category_id)->toBe($this->other->id)
        ->and($chain[1]->start_time)->toEqual(Carbon::parse('2026-01-01 09:00:00'))
        ->and($chain[1]->end_time)->toEqual(Carbon::parse('2026-01-01 10:00:00'))
        ->and($chain[1]->connected_shift_id)->toBeNull();
});

test('consecutive shifts stay connected 1 -> 2 -> 3 -> ... until stopped', function () {
    $this->post(route('timer.start', $this->work));

    at('09:00:00');
    $this->post(route('timer.update', $this->other));

    at('10:00:00');
    $this->post(route('timer.pause'));

    at('10:15:00');
    $this->post(route('timer.unpause', $this->work));

    at('11:00:00');
    $this->post(route('timer.update', $this->other));

    // The session start survives every update and unpause, only startedAt moves
    expect(timerStatus($this->user))
        ->createdAt->toEqual(Carbon::parse('2026-01-01 08:00:00'))
        ->startedAt->toEqual(Carbon::parse('2026-01-01 11:00:00'));

    at('12:00:00');
    $this->post(route('timer.stop'));

    $shifts = Shift::all();
    $chain = shiftChain($this->user);

    // Every created shift must be reachable by following the links
    expect($chain)->toHaveCount($shifts->count());

    // Each shift points to the shift that starts when it ends, the last one to nothing
    foreach ($chain as $i => $shift) {
        $next = $chain[$i + 1] ?? null;

        expect($shift->connected_shift_id)->toBe($next?->id);

        if ($next) {
            expect($shift->end_time)->toEqual($next->start_time);
        }
    }

    expect(collect($chain)->map(fn (Shift $s) => $s->category->name)->all())
        ->toBe(['Work', 'Other', 'Break', 'Work', 'Other'])
        ->and(timerStatus($this->user))->toBeNull();
});

test('a new session after stop gets a fresh createdAt', function () {
    $this->post(route('timer.start', $this->work));
    at('09:00:00');
    $this->post(route('timer.stop'));

    at('10:00:00');
    $this->post(route('timer.start', $this->other));

    expect(timerStatus($this->user))
        ->createdAt->toEqual(Carbon::parse('2026-01-01 10:00:00'))
        ->startedAt->toEqual(Carbon::parse('2026-01-01 10:00:00'));
});

test('a stopped timer does not link to the next session', function () {
    $this->post(route('timer.start', $this->work));
    at('09:00:00');
    $this->post(route('timer.stop'));

    at('10:00:00');
    $this->post(route('timer.start', $this->other));
    at('11:00:00');
    $this->post(route('timer.stop'));

    $shifts = Shift::orderBy('id')->get();

    expect($shifts)->toHaveCount(2)
        ->and($shifts[0]->connected_shift_id)->toBeNull()
        ->and($shifts[1]->connected_shift_id)->toBeNull();
});
