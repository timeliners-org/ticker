<?php

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('creating a team makes its creator an admin and creates the default category', function () {
    $response = $this->post(route('teams.create'), [
        'name' => 'New_Team9',
    ]);

    $team = Team::where('name', 'New_Team9')->sole();

    $response->assertRedirect(route('teams.view', ['teamId' => $team->id]));

    expect($this->user->isAdminOf($team))->toBeTrue()
        ->and(TeamMember::where('team_id', $team->id)->where('user_id', $this->user->id)->count())->toBe(1);

    $this->assertDatabaseHas('shift_categories', [
        'owner_id' => $team->id,
        'name' => 'Default',
        'color' => '#77fc03',
    ]);
});

test('a team member can leave a team', function () {
    $team = Team::factory()->create();
    $membership = $team->members()->create([
        'user_id' => $this->user->id,
        'is_admin' => false,
    ]);

    $this->post(route('teams.leave', $team->id))
        ->assertRedirect(route('teams.index'));

    $this->assertDatabaseMissing('team_members', ['id' => $membership->id]);
    $this->assertDatabaseHas('teams', ['id' => $team->id]);
});

test('an admin can update a team', function () {
    $team = Team::factory()->create();
    $team->members()->create([
        'user_id' => $this->user->id,
        'is_admin' => true,
    ]);

    $this->post(route('teams.update', $team->id), ['name' => 'Updated_Team'])
        ->assertRedirect();

    expect($team->fresh()->name)->toBe('Updated_Team');
});

test('a non-admin cannot update a team', function () {
    $team = Team::factory()->create();
    $team->members()->create([
        'user_id' => $this->user->id,
        'is_admin' => false,
    ]);

    $this->post(route('teams.update', $team->id), ['name' => 'Updated_Team'])
        ->assertForbidden();

    expect($team->fresh()->name)->not->toBe('Updated_Team');
});

test('an admin can delete a team', function () {
    $team = Team::factory()->create();
    $team->members()->create([
        'user_id' => $this->user->id,
        'is_admin' => true,
    ]);

    $this->delete(route('teams.destroy', $team->id))
        ->assertRedirect(route('teams.index'));

    $this->assertDatabaseMissing('teams', ['id' => $team->id]);
});

test('a non-admin cannot delete a team', function () {
    $team = Team::factory()->create();
    $team->members()->create([
        'user_id' => $this->user->id,
        'is_admin' => false,
    ]);

    $this->delete(route('teams.destroy', $team->id))
        ->assertForbidden();

    $this->assertDatabaseHas('teams', ['id' => $team->id]);
});
