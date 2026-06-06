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
use App\Http\Requests\AssignTicketRequest;

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
    // Obtenemos únicamente los filtros ya validados por IndexTicketRequest
    $filters = $request->validated();

    // Consulta base: cargamos el usuario creador y el agente asignado para evitar N+1 queries
    $query = Ticket::with(['user', 'assignedTo']);

    // Seguridad principal:
    // Si el usuario autenticado NO es staff, solo puede ver sus propios tickets
    if (! $request->user()->isStaff()) {
        $query->where('user_id', $request->user()->id);
    }

    // Filtrar por estado del ticket
    if (! empty($filters['status'])) {
        $query->where('status', $filters['status']);
    }

    // Filtrar por prioridad del ticket
    if (! empty($filters['priority'])) {
        $query->where('priority', $filters['priority']);
    }

    // Buscar por título o descripción
    if (! empty($filters['search'])) {
        $search = $filters['search'];

        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%');
        });
    }

    // Filtrar tickets asignados al usuario autenticado
    // Ejemplo: GET /api/tickets?assigned=me
    if (! empty($filters['assigned']) && $filters['assigned'] === 'me') {
        $query->where('assigned_to_id', $request->user()->id);
    }

    // Filtrar tickets sin asignar
    // Ejemplo: GET /api/tickets?assigned=unassigned
    if (! empty($filters['assigned']) && $filters['assigned'] === 'unassigned') {
        $query->whereNull('assigned_to_id');
    }

    // Filtrar tickets asignados a un agente específico
    // Ejemplo: GET /api/tickets?assigned_to_id=3
    if (! empty($filters['assigned_to_id'])) {
        $query->where('assigned_to_id', $filters['assigned_to_id']);
    }

    // Ordenamiento dinámico con valores por defecto
    $sort = $filters['sort_by'] ?? 'created_at';
    $sortDirection = $filters['sort_direction'] ?? 'desc';

    // Columnas permitidas para ordenar.
    // Esto evita que se intente ordenar por columnas no permitidas.
    $allowedSorts = ['created_at', 'title', 'priority', 'status'];

    // Si sort_by es inválido, se ignora también la dirección enviada
    // y se vuelve al orden por defecto: created_at desc.
    if (! in_array($sort, $allowedSorts, true)) {
        $sort = 'created_at';
        $sortDirection = 'desc';
    }

    // Si sort_by es válido pero sort_direction es inválido,
    // se usa desc como dirección por defecto.
    if (! in_array(strtolower($sortDirection), ['asc', 'desc'], true)) {
        $sortDirection = 'desc';
    }

    // Aplicamos el orden una sola vez
    $query->orderBy($sort, $sortDirection);

    // Paginación de resultados
    $tickets = $query->paginate(10);

    // Respuesta JSON con tickets y metadatos de paginación
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
    public function store(StoreTicketRequest $request)
    {
    $validated = $request->validated();

    $validated['user_id'] = $request->user()->id;

    $ticket = Ticket::create($validated);

    $ticket->load (['user', 'assignedTo']);
    
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

    $ticket->load(['user', 'assignedTo']);

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

    $ticket->load(['user', 'assignedTo']);

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

    $ticket->load(['user', 'assignedTo']);

    return response()->json([
        'message' => 'Ticket status updated successfully',
        'ticket' => new TicketResource($ticket)
    ]);
    }

    public function assign(AssignTicketRequest $request, Ticket $ticket)
    {
    if ($request->user()->cannot('assign', $ticket)) {
        return response()->json([
            'message' => 'Unauthorized',
        ], 403);
    }

    $ticket->update([
        'assigned_to_id' => $request->validated()['assigned_to_id'] ?? null,
    ]);

    $ticket->load(['user', 'assignedTo']);

    return response()->json([
        'message' => $ticket->assigned_to_id
            ? 'Ticket assigned successfully'
            : 'Ticket unassigned successfully',
        'ticket' => new TicketResource($ticket),
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