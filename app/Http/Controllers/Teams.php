<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class Teams extends Controller
{
    public function index(Request $request): Response
    {
        /**
         * @var User
         */
        $user = $request->user();

        $teams = $user->teams()->get()->map(function ($team) {
            return [
                'id' => $team->id,
                'name' => $team->name,
                'members' => $team->members()->count(),
            ];
        });

        return Inertia::render('Teams/Index', [
            'teams' => $teams,
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        /**
         * @var User
         */
        $user = $request->user();

        $validated = $request->validate([
            'name' => $this->nameRules(),
        ]);

        $team = $user->teams()->create(
            ['name' => $validated['name']],
            ['is_admin' => true],
        );

        $team->shiftCategories()->create([
            'name' => 'Default',
            'color' => '#77fc03',
        ]);

        return redirect()->route('teams.view', ['teamId' => $team->id]);
    }

    public function view(Request $request, int $teamId): Response
    {
        /**
         * @var User
         */
        $user = $request->user();

        $team = $user->teams()->findOrFail($teamId);

        return Inertia::render('Teams/View', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'members' => $team->members()->with('user:id,name')->get(),
                'shiftCategories' => $team->shiftCategories()->get(),
                'is_admin' => $user->isAdminOf($team),
            ],
        ]);
    }

    public function update(Request $request, int $teamId): RedirectResponse
    {
        /**
         * @var User
         */
        $user = $request->user();

        $team = $user->teams()->findOrFail($teamId);

        if (! $user->isAdminOf($team)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => $this->nameRules(),
        ]);

        $team->update([
            'name' => $validated['name'],
        ]);

        return back();
    }

    public function destroy(Request $request, int $teamId): RedirectResponse
    {
        /**
         * @var User
         */
        $user = $request->user();

        $team = $user->teams()->findOrFail($teamId);

        if (! $user->isAdminOf($team)) {
            abort(403);
        }

        $team->delete();

        return redirect()->route('teams.index');
    }

    public function leave(Request $request, int $teamId): RedirectResponse
    {
        // TODO: Add a check to prevent the last admin from leaving the team
        /**
         * @var User
         */
        $user = $request->user();

        $team = $user->teams()->findOrFail($teamId);

        $user->memberships()->where('team_id', $team->id)->delete();

        return redirect()->route('teams.index');
    }

    public function addCategory(Request $request, int $teamId): RedirectResponse
    {
        /**
         * @var User
         */
        $user = $request->user();

        $team = $user->teams()->findOrFail($teamId);

        if (! $user->isAdminOf($team)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => $this->nameRules(),
            'color' => $this->colorRules(),
        ]);

        $team->shiftCategories()->create([
            'name' => $validated['name'],
            'color' => $validated['color'],
        ]);

        return back();
    }

    /** @return array<int, string> */
    private function nameRules(): array
    {
        return ['required', 'string', 'max:255', 'regex:/\A[A-Za-z0-9_]+\z/'];
    }

    /** @return array<int, string> */
    private function colorRules(): array
    {
        return ['required', 'string', 'regex:/\A#[0-9A-Fa-f]{6}(?:[0-9A-Fa-f]{2})?\z/'];
    }
}
