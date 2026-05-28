<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Http\Requests\StoreticketRequest;
use App\Http\Resources\TicketResource;

class TicketController extends Controller
{
    // Listar tickets
    public function index()
    {
        $tickets = Ticket::with('user')->latest()->get();

        return TicketResource::collection($tickets);
    }

    // Crear ticket
    public function store(StoreticketRequest $request)
    {
        $validated = $request->validate();

        $validated['user_id'] = $request->user()->id;

        $ticket = Ticket::create($validated);

        return response()->json([
            'message' => 'Ticket created successfully',
            'ticket' => new TicketResource($ticket)
        ], 201);
    }

    // Mostrar ticket individual
    public function show(string $id)
    {
        $ticket = Ticket::with('user')->findOrFail($id);

        return new TicketResource($ticket);
    }

    // Actualizar ticket
    public function update(StoreticketRequest $request, string $id)
    {
        $ticket = Ticket::findOrFail($id);
        $this->authorize('update', $ticket);
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:open,in_progress,closed',
            'priority' => 'sometimes|in:low,medium,high'
        ]);

        $ticket->update($validated);

        return response()->json([
            'message' => 'Ticket updated successfully',
            'ticket' => new TicketResource($ticket)
        ]);
    }

    // Eliminar ticket
    public function destroy(string $id)
    {
        $ticket = Ticket::findOrFail($id);

        $this->authorize('delete', $ticket);

        $ticket->delete();

        return response()->json([
            'message' => 'Ticket deleted successfully'
        ]);
    }
}