<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketTagRequest;
use App\Http\Requests\UpdateTicketTagRequest;
use App\Http\Resources\TicketTagResource;
use App\Models\TicketTag;
use Illuminate\Http\Request;

class TicketTagController extends Controller
{
    public function index(Request $request)
    {
        $query = TicketTag::query()->orderBy('name');

        if (! $request->user()->isAdmin()) {
            $query->where('is_active', true);
        }

        return response()->json([
            'tags' => TicketTagResource::collection($query->get()),
        ]);
    }

    public function store(StoreTicketTagRequest $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tag = TicketTag::create($request->validated());

        return response()->json([
            'message' => 'Ticket tag created successfully',
            'tag' => new TicketTagResource($tag),
        ], 201);
    }

    public function show(Request $request, TicketTag $ticketTag)
    {
        if (! $ticketTag->is_active && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'tag' => new TicketTagResource($ticketTag),
        ]);
    }

    public function update(UpdateTicketTagRequest $request, TicketTag $ticketTag)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ticketTag->update($request->validated());

        return response()->json([
            'message' => 'Ticket tag updated successfully',
            'tag' => new TicketTagResource($ticketTag),
        ]);
    }

    public function destroy(Request $request, TicketTag $ticketTag)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ticketTag->delete();

        return response()->json([
            'message' => 'Ticket tag deleted successfully',
        ]);
    }
}
