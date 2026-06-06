<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Resources\TicketResource;
use OpenApi\Attributes as OA;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Http\Requests\IndexTicketRequest;
use App\Models\TicketComment;

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
    public function index(IndexTicketRequest $request)
    {
        $filters=$request->validated();

    // 1. Base de la consulta vinculada al usuario (Siempre protegida)
    $query = Ticket::with('user');

    if (! $request->user()->isStaff()) {
        $query->where('user_id', $request->user()->id);
    }

    // 2. Filtrar por status
    if (! empty($filters['status'])) {
    $query->where('status', $filters['status']);
    }

    // 3. Filtrar por prioridad
    if (! empty($filters['priority'])) {
    $query->where('priority', $filters['priority']);
    }

    // 4. Buscar por título o descripción (Agrupado en un closure seguro)
    if (! empty($filters['search'])) {
    $search = $filters['search'];

    $query->where(function ($q) use ($search) {
        $q->where('title', 'like', '%' . $search . '%')
            ->orWhere('description', 'like', '%' . $search . '%');
    });
    }

    // 5. Ordenar dinámicamente
    $sort = $filters['sort_by'] ?? 'created_at';
    $sortDirection = $filters['sort_direction'] ?? 'desc';

    // Forzar dirección válida
    $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? $sortDirection : 'desc';

    $allowedSorts = ['created_at', 'title', 'priority', 'status'];
    
    if (in_array($sort, $allowedSorts)) {
        $query->orderBy($sort, $sortDirection);
    } else {
        $query->orderBy('created_at', 'desc');
    }

    // 6. Paginación (Se eliminó latest() para no romper el orden anterior)
    $tickets = $query->paginate(10);

    return response()->json([
        'tickets' => TicketResource::collection($tickets),
        'pagination' => [
            'total'        => $tickets->total(),
            'per_page'     => $tickets->perPage(),
            'current_page' => $tickets->currentPage(),
            'last_page'    => $tickets->lastPage(),
            'from'         => $tickets->firstItem(),
            'to'           => $tickets->lastItem(),
        ],
    ]);
    }


    // Crear ticket
    public function store(StoreTicketRequest $request)
    {
    $validated = $request->validated();

    $validated['user_id'] = $request->user()->id;

    $ticket = Ticket::create($validated);

    $ticket->load ('user');
    
    return response()->json([
        'message' => 'Ticket created successfully',
        'ticket' => new TicketResource($ticket)
    ], 201);
    }


    // Mostrar ticket individual
   public function show(Request $request, Ticket $ticket)
    {
    if ($request->user()->cannot('view', $ticket)) {
        return response()->json([
            'message' => 'Unauthorized',
        ], 403);
    }

    $ticket->load('user');

    return response()->json([
        'ticket' => new TicketResource($ticket),
    ]);
    }

    // Actualizar ticket
   public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
    if ($request->user()->cannot('update', $ticket)) {
    return response()->json([
        'message' => 'Unauthorized',
    ], 403);
    }

    $ticket->update($request->validated());

    $ticket->load('user');

    return response()->json([
    'message' => 'Ticket updated successfully',
    'ticket' => new TicketResource($ticket),
    ]);
    }


    //Actualizar solo el estado del ticket
    public function updateStatus(UpdateTicketStatusRequest $request, Ticket $ticket)
    {
   if ($request->user()->cannot('update', $ticket)) {
    return response()->json([
        'message' => 'Unauthorized',
    ], 403);
    }

    $ticket->update($request->validated());

    $ticket->load('user');

    return response()->json([
        'message' => 'Ticket status updated successfully',
        'ticket' => new TicketResource($ticket)
    ]);
    }

    // Eliminar ticket
    public function destroy(Request $request, Ticket $ticket)
    {
    if ($request->user()->cannot('delete', $ticket)) {
        return response()->json([
            'message' => 'Unauthorized',
        ], 403);
    }

    $ticket->delete();

    return response()->json([
        'message' => 'Ticket deleted successfully',
    ]);
    }
}