<?php

use App\Models\Quota;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('a quota can be created for a day', function () {
    $this->postJson('/quotas', ['date' => '2026-11-05', 'quota' => 8 * 3600])
        ->assertCreated();

    expect(Quota::where('user_id', $this->user->id)->count())->toBe(1);
});

test('only one quota can be set per day', function () {
    Quota::factory()->create(['user_id' => $this->user->id, 'date' => '2026-11-05', 'quota' => 3600]);

    $this->postJson('/quotas', ['date' => '2026-11-05', 'quota' => 7200])
        ->assertUnprocessable();

    expect(Quota::where('user_id', $this->user->id)->count())->toBe(1);
});

test('a quota cannot be moved to a day that already has one', function () {
    Quota::factory()->create(['user_id' => $this->user->id, 'date' => '2026-11-05']);
    $other = Quota::factory()->create(['user_id' => $this->user->id, 'date' => '2026-11-06']);

    $this->putJson("/quotas/{$other->id}", ['date' => '2026-11-05', 'quota' => 3600])
        ->assertUnprocessable();
});

test('a quota cannot be negative', function () {
    $this->postJson('/quotas', ['date' => '2026-11-05', 'quota' => -1])
        ->assertUnprocessable();

    expect(Quota::count())->toBe(0);
});

test('a quota cannot exceed 24 hours', function () {
    $this->postJson('/quotas', ['date' => '2026-11-05', 'quota' => 24 * 3600 + 1])
        ->assertUnprocessable();

    expect(Quota::count())->toBe(0);
});

test('a quota of exactly 24 hours is allowed', function () {
    $this->postJson('/quotas', ['date' => '2026-11-05', 'quota' => 24 * 3600])
        ->assertCreated();
});

test('updating a quota rejects negative and too large values', function () {
    $quota = Quota::factory()->create(['user_id' => $this->user->id, 'date' => '2026-11-05', 'quota' => 3600]);

    $this->putJson("/quotas/{$quota->id}", ['date' => '2026-11-05', 'quota' => -5])
        ->assertUnprocessable();
    $this->putJson("/quotas/{$quota->id}", ['date' => '2026-11-05', 'quota' => 24 * 3600 + 1])
        ->assertUnprocessable();

    expect($quota->fresh()->quota)->toBe(3600);
});

test('quotas of other users cannot be changed or deleted', function () {
    $quota = Quota::factory()->create(['user_id' => User::factory()->create()->id, 'date' => '2026-11-05']);

    $this->putJson("/quotas/{$quota->id}", ['date' => '2026-11-05', 'quota' => 3600])
        ->assertForbidden();
    $this->deleteJson("/quotas/{$quota->id}")->assertForbidden();
});
