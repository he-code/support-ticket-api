<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignTicketRequest;
use App\Http\Requests\IndexTicketRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketStatusChangedNotification;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    // Listar tickets con filtros, búsqueda, asignación, ordenamiento y paginación
    public function index(IndexTicketRequest $request)
    {
        // Obtenemos únicamente los filtros ya validados por IndexTicketRequest
        $filters = $request->validated();

        // Consulta base: cargamos el usuario creador y el agente asignado para evitar N+1 queries
        $query = Ticket::with(['user', 'assignedTo', 'category']);

        // Seguridad principal:
        // Si el usuario autenticado NO es staff, solo puede ver sus propios tickets
        if (! $request->user()->isStaff()) {
            $query->where('user_id', $request->user()->id);
        }

        // Filtrar por categoría
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
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
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
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
        // Validamos los datos del request
        $validated = $request->validated();

        // Asociamos el ticket al usuario autenticado
        $validated['user_id'] = $request->user()->id;

        // Creamos el ticket
        $ticket = Ticket::create($validated);

        // Registramos actividad: ticket creado
        $ticket->recordActivity(
            type: 'ticket_created',
            user: $request->user(),
            description: 'Ticket created'
        );

        // Cargamos relaciones para devolver una respuesta completa
        $ticket->load(['user', 'assignedTo', 'category']);

        return response()->json([
            'message' => 'Ticket created successfully',
            'ticket' => new TicketResource($ticket),
        ], 201);
    }

    // Mostrar ticket individual
    public function show(Request $request, Ticket $ticket)
    {
        // Solo puede ver el ticket quien tenga permiso según TicketPolicy
        if ($request->user()->cannot('view', $ticket)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Cargamos relaciones necesarias para el recurso
        $ticket->load(['user', 'assignedTo', 'category']);

        return response()->json([
            'ticket' => new TicketResource($ticket),
        ]);
    }

    // Actualizar ticket
    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        // Solo puede actualizar el ticket quien tenga permiso según TicketPolicy
        if ($request->user()->cannot('update', $ticket)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Guardamos los valores originales antes de actualizar
        // Esto nos permite registrar en el historial qué cambió
        $original = $ticket->only([
            'title',
            'description',
            'status',
            'priority',
            'category_id',
        ]);

        // Actualizamos el ticket con los datos validados
        $ticket->update($request->validated());

        // Obtenemos los campos que realmente cambiaron
        // Excluimos updated_at porque no es relevante para el historial funcional
        $changes = collect($ticket->getChanges())
            ->except(['updated_at'])
            ->toArray();

        // Si hubo cambios reales, registramos actividad
        if ($changes !== []) {
            $oldValues = array_intersect_key($original, $changes);

            $ticket->recordActivity(
                type: 'ticket_updated',
                user: $request->user(),
                description: 'Ticket updated',
                metadata: [
                    'old' => $oldValues,
                    'new' => $changes,
                ]
            );
        }

        // Cargamos relaciones para devolver una respuesta completa
        $ticket->load(['user', 'assignedTo', 'category']);

        return response()->json([
            'message' => 'Ticket updated successfully',
            'ticket' => new TicketResource($ticket),
        ]);
    }

    // Actualizar solo el estado del ticket
    public function updateStatus(UpdateTicketStatusRequest $request, Ticket $ticket)
    {
        // Solo puede cambiar el estado quien tenga permiso según TicketPolicy
        if ($request->user()->cannot('update', $ticket)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Guardamos el estado anterior para registrar el cambio
        $oldStatus = $ticket->status;

        // Actualizamos el estado
        $ticket->update([
            'status' => $request->validated()['status'],
        ]);

        // Si hubo cambio real de estado, registramos actividad y notificamos al dueño.
        // No notificamos al mismo usuario que realizó el cambio.
        if ($oldStatus !== $ticket->status) {
            $ticket->recordActivity(
                type: 'status_changed',
                user: $request->user(),
                description: 'Ticket status changed',
                oldValue: $oldStatus,
                newValue: $ticket->status
            );

            $ticket->loadMissing('user');

            if ($ticket->user_id !== $request->user()->id) {
                $ticket->user?->notify(
                    new TicketStatusChangedNotification($ticket, $oldStatus, $ticket->status)
                );
            }
        }

        // Cargamos relaciones para devolver una respuesta completa
        $ticket->load(['user', 'assignedTo', 'category']);

        return response()->json([
            'message' => 'Ticket status updated successfully',
            'ticket' => new TicketResource($ticket),
        ]);
    }

    // Asignar o desasignar ticket a un agente de soporte
    public function assign(AssignTicketRequest $request, Ticket $ticket)
    {
        // Solo staff puede asignar o desasignar tickets
        if ($request->user()->cannot('assign', $ticket)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Guardamos el agente anterior antes de actualizar
        $oldAssignedToId = $ticket->assigned_to_id;

        // Si assigned_to_id viene con ID, se asigna.
        // Si viene null o no viene, se desasigna.
        $ticket->update([
            'assigned_to_id' => $request->validated()['assigned_to_id'] ?? null,
        ]);

        // Guardamos el nuevo agente asignado
        $newAssignedToId = $ticket->assigned_to_id;

        // Si hubo cambio real de asignación, registramos actividad y notificamos al nuevo agente.
        // No enviamos una notificación duplicada si se vuelve a asignar al mismo agente.
        if ($oldAssignedToId !== $newAssignedToId) {
            $ticket->recordActivity(
                type: $newAssignedToId ? 'ticket_assigned' : 'ticket_unassigned',
                user: $request->user(),
                description: $newAssignedToId
                    ? 'Ticket assigned to support agent'
                    : 'Ticket unassigned from support agent',
                oldValue: $oldAssignedToId ? (string) $oldAssignedToId : null,
                newValue: $newAssignedToId ? (string) $newAssignedToId : null
            );

            if ($newAssignedToId && $newAssignedToId !== $request->user()->id) {
                $assignedUser = User::find($newAssignedToId);

                $assignedUser?->notify(new TicketAssignedNotification($ticket));
            }
        }
        // Cargamos relaciones para devolver una respuesta completa
        $ticket->load(['user', 'assignedTo', 'category']);

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
        // Solo puede eliminar el ticket quien tenga permiso según TicketPolicy
        if ($request->user()->cannot('delete', $ticket)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Eliminamos el ticket.
        // Nota: sus actividades se eliminarán también si la FK tiene cascadeOnDelete.
        $ticket->delete();

        return response()->json([
            'message' => 'Ticket deleted successfully',
        ]);
    }
}
