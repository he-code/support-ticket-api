<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketChannelRequest;
use App\Http\Requests\UpdateTicketChannelRequest;
use App\Http\Resources\TicketChannelResource;
use App\Models\TicketChannel;
use Illuminate\Http\Request;

class TicketChannelController extends Controller
{
    public function index(Request $request)
    {
        $query = TicketChannel::query()->orderBy('name');

        if (! $request->user()->isAdmin()) {
            $query->where('is_active', true);
        }

        return response()->json([
            'channels' => TicketChannelResource::collection($query->get()),
        ]);
    }

    public function store(StoreTicketChannelRequest $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $channel = TicketChannel::create($request->validated());

        return response()->json([
            'message' => 'Ticket channel created successfully',
            'channel' => new TicketChannelResource($channel),
        ], 201);
    }

    public function show(Request $request, TicketChannel $ticketChannel)
    {
        if (! $ticketChannel->is_active && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'channel' => new TicketChannelResource($ticketChannel),
        ]);
    }

    public function update(UpdateTicketChannelRequest $request, TicketChannel $ticketChannel)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ticketChannel->update($request->validated());

        return response()->json([
            'message' => 'Ticket channel updated successfully',
            'channel' => new TicketChannelResource($ticketChannel),
        ]);
    }

    public function destroy(Request $request, TicketChannel $ticketChannel)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ticketChannel->delete();

        return response()->json([
            'message' => 'Ticket channel deleted successfully',
        ]);
    }
}
