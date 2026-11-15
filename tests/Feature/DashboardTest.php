<?php

use App\Models\Quota;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard includes the quota for today', function () {
    $user = User::factory()->create();
    Quota::factory()->create([
        'user_id' => $user->id,
        'date' => today()->toDateString(),
        'quota' => 8 * 60 * 60,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('todayQuota', 8 * 60 * 60));
});
