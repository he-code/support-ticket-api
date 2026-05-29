<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Http\Requests\StoreticketRequest;
use App\Http\Resources\TicketResource;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use App\Http\Requests\UpdateTicketRequest;

    class TicketController extends Controller
    {
    #[OA\Get(
    path: '/api/tickets',
    summary: 'Get all tickets',
    security: [['bearerAuth' => []]],
    tags: ['Tickets'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'List of tickets'
        )
    ]
    )]
    // Listar tickets
    public function index(Request $request)
    {
        //Filtrar por status
        $query= Ticket::where('user_id', $request->user()->id);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        //Filtrar por prioridad
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
    }

        //Buscar por título o descripción
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                   ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        //Ordenar por fecha de creación
        $short= $request->get('sort_by', 'created_at');
        $shortDirection= $request->get('sort_direction', 'desc');

        $allowedSorts = ['created_at', 'title', 'priority', 'status'];
        if (!in_array($short, $allowedSorts)) {
            $query->orderBy('created_at', 'desc');
        } else {
            $query->orderBy($short, $shortDirection);
        }

        $tickets = $query->latest()->paginate(10);

        return response()->json([
            'tickets' => TicketResource::collection($tickets),
            'pagination' => [
                'total' => $tickets->total(),
                'per_page' => $tickets->perPage(),
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'from' => $tickets->firstItem(),
                'to' => $tickets->lastItem(),
            ],
        ]);
    }

    // Crear ticket
    public function store(StoreticketRequest $request)
    {
    $validated = $request->validated();

    $validated['user_id'] = $request->user()->id;

    $ticket = Ticket::create($validated);

    return response()->json([
        'message' => 'Ticket created successfully',
        'ticket' => new TicketResource($ticket)
    ], 201);
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
    $request->validate([
        'status' => 'required|in:open,in_progress,resolved,closed',
    ]);

    $ticket->update([
        'status' => $request->status,
    ]);

    return response()->json([
        'message' => 'Ticket status updated successfully',
        'ticket' => new TicketResource($ticket)
    ]);
    }

    // Mostrar ticket individual
    public function show(Ticket $ticket)
    {
        //Verificar que el ticket pertenezca al usuario autenticado
        if($ticket->user_id !== Auth::id()){
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        return response()->json([
            'ticket' => new TicketResource($ticket)
        ]);
    }

    // Actualizar ticket
   public function update(UpdateTicketRequest $request, string $id)
    {
    $ticket = Ticket::findOrFail($id);

    if ($request->user()->cannot('update', $ticket)) {
        return response()->json([
            'message' => 'Unauthorized'
        ], 403);
    }


    $ticket->update($request->validated());

    return response()->json([
        'message' => 'Ticket updated successfully',
        'ticket' => new TicketResource($ticket)
    ]);
    }

    // Eliminar ticket
    public function destroy(Request $request, string $id)
    {
    $ticket = Ticket::findOrFail($id);

    if ($request->user()->cannot('delete', $ticket)) {
        return response()->json([
            'message' => 'Unauthorized'
        ], 403);
    }

    $ticket->delete();

    return response()->json([
        'message' => 'Ticket deleted successfully'
    ]);
    }
}