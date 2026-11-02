<?php

use App\Models\Shift;
use App\Models\ShiftCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('shifts index lists available dates for the authenticated user', function () {
    $user = User::factory()->create();
    $category = ShiftCategory::factory()->create();

    Shift::create([
        'user_id' => $user->id,
        'shift_category_id' => $category->id,
        'start_time' => '2026-10-31 08:00:00',
        'end_time' => '2026-10-31 09:00:00',
    ]);

    $this->actingAs($user)
        ->get(route('shifts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Shifts/Index')
            ->where('datesWithShifts', ['2026-10-31']));
});

test('daily shifts include categories and only return the authenticated user shifts', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = ShiftCategory::factory()->create(['name' => 'Focus', 'color' => '#2D8A6E']);

    $firstShift = Shift::create([
        'user_id' => $user->id,
        'shift_category_id' => $category->id,
        'start_time' => '2026-10-31 08:00:00',
        'end_time' => '2026-10-31 09:00:00',
    ]);
    $secondShift = Shift::create([
        'user_id' => $user->id,
        'shift_category_id' => $category->id,
        'start_time' => '2026-10-31 09:00:00',
        'end_time' => '2026-10-31 10:00:00',
    ]);
    $firstShift->update(['connected_shift_id' => $secondShift->id]);
    Shift::create([
        'user_id' => $otherUser->id,
        'shift_category_id' => $category->id,
        'start_time' => '2026-10-31 11:00:00',
        'end_time' => '2026-10-31 12:00:00',
    ]);

    $this->actingAs($user)
        ->getJson(route('shifts.daily', ['date' => '2026-10-31']))
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.category.name', 'Focus')
        ->assertJsonPath('0.category.color', '#2D8A6E')
        ->assertJsonPath('0.connected_shift_id', $secondShift->id)
        ->assertJsonPath('1.id', $secondShift->id);
});
