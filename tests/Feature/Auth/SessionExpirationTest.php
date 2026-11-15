<?php

use App\Models\User;

test('unauthenticated regular request redirects to login', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('unauthenticated Inertia request returns location header', function () {
    $this->withHeaders(['X-Inertia' => 'true'])
        ->get(route('dashboard'))
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', route('login'));
});

test('authenticated user can access dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('JSON request returns 401 when unauthenticated', function () {
    $this->withHeaders(['Accept' => 'application/json'])
        ->post(route('timer.start', ['category' => 1]))
        ->assertStatus(401)
        ->assertJson(['message' => 'Unauthenticated.']);
});
