<?php

namespace App\Http\Controllers;

use App\Models\Quota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class Quotas extends Controller
{
    public function getDaily(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $quota = Quota::where('user_id', $user->id)
            ->where('date', $validated['date'])
            ->firstOrFail(['id', 'quota', 'date']);

        return response()->json($quota);
    }

    public function getAll(Request $request): JsonResponse
    {
        $user = auth()->user();

        $quotas = $user->quotas()->get(['date', 'quota']);

        return response()->json(['success' => true, 'quotas' => $quotas]);
    }

    public function create(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'date' => 'required|date',
            'quota' => 'required|integer',
        ]);

        if ($validated['quota'] > 24 * 60 * 60 || $validated['quota'] < 0) {
            return response()->json(['error' => 'Quota cannot exceed 24 hours or be negative'], 422);
        }

        if (Quota::where('user_id', $user->id)->whereDate('date', $validated['date'])->exists()) {
            return response()->json(['error' => 'Quota for this date already exists'], 422);
        }

        $quota = new Quota($validated);
        $quota->user()->associate($user);

        $quota->save();

        return response()->json(['success' => true, 'quota' => $quota], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'date' => 'required|date',
            'quota' => 'required|integer',
        ]);

        if ($validated['quota'] > 24 * 60 * 60 || $validated['quota'] < 0) {
            return response()->json(['error' => 'Quota cannot exceed 24 hours or be negative'], 422);
        }

        if (Quota::where('user_id', $user->id)->whereDate('date', $validated['date'])->where('id', '!=', $id)->exists()) {
            return response()->json(['error' => 'Quota for this date already exists'], 422);
        }

        $quota = Quota::findOrFail($id);
        if ($quota->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $quota->update($validated);

        return response()->json(['success' => true, 'quota' => $quota], 200);
    }

    public function delete(Request $request, int $id): JsonResponse
    {
        $user = auth()->user();

        $quota = Quota::findOrFail($id);
        if ($quota->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $quota->delete();

        return response()->json(['success' => true], 200);
    }
}
