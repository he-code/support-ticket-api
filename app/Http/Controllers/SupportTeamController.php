<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupportTeamRequest;
use App\Http\Requests\UpdateSupportTeamRequest;
use App\Http\Resources\SupportTeamResource;
use App\Models\SupportTeam;
use Illuminate\Http\Request;

class SupportTeamController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTeam::query()
            ->with('members')
            ->orderBy('name');

        if (! $request->user()->isAdmin()) {
            $query->where('is_active', true);
        }

        return response()->json([
            'teams' => SupportTeamResource::collection($query->get()),
        ]);
    }

    public function store(StoreSupportTeamRequest $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();
        $team = SupportTeam::create(collect($validated)->except('member_ids')->toArray());
        $team->members()->sync($validated['member_ids'] ?? []);
        $team->load('members');

        return response()->json([
            'message' => 'Support team created successfully',
            'team' => new SupportTeamResource($team),
        ], 201);
    }

    public function show(Request $request, SupportTeam $supportTeam)
    {
        if (! $supportTeam->is_active && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $supportTeam->load('members');

        return response()->json([
            'team' => new SupportTeamResource($supportTeam),
        ]);
    }

    public function update(UpdateSupportTeamRequest $request, SupportTeam $supportTeam)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();
        $supportTeam->update(collect($validated)->except('member_ids')->toArray());

        if (array_key_exists('member_ids', $validated)) {
            $supportTeam->members()->sync($validated['member_ids'] ?? []);
        }

        $supportTeam->load('members');

        return response()->json([
            'message' => 'Support team updated successfully',
            'team' => new SupportTeamResource($supportTeam),
        ]);
    }

    public function destroy(Request $request, SupportTeam $supportTeam)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $supportTeam->delete();

        return response()->json([
            'message' => 'Support team deleted successfully',
        ]);
    }
}
