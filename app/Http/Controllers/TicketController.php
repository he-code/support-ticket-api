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
use App\Services\AutomationService;
use App\Services\CustomFieldValueService;
use App\Services\IntegrationEventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function index(IndexTicketRequest $request)
    {
        $filters = $request->validated();

        $query = Ticket::with($this->ticketRelations());

        if (! $request->user()->isStaff()) {
            $query->where('user_id', $request->user()->id);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['channel_id'])) {
            $query->where('channel_id', $filters['channel_id']);
        }

        if (! empty($filters['team_id'])) {
            $query->where('team_id', $filters['team_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['assigned']) && $filters['assigned'] === 'me') {
            $query->where('assigned_to_id', $request->user()->id);
        }

        if (! empty($filters['assigned']) && $filters['assigned'] === 'unassigned') {
            $query->whereNull('assigned_to_id');
        }

        if (! empty($filters['assigned_to_id'])) {
            $query->where('assigned_to_id', $filters['assigned_to_id']);
        }

        if (! empty($filters['tag_id'])) {
            $query->whereHas('tags', fn ($q) => $q->whereKey($filters['tag_id']));
        }

        if (! empty($filters['tag_ids'])) {
            $query->whereHas('tags', fn ($q) => $q->whereIn('ticket_tags.id', $filters['tag_ids']));
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        if (! empty($filters['due_before'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('first_response_due_at', '<=', $filters['due_before'])
                    ->orWhere('resolution_due_at', '<=', $filters['due_before']);
            });
        }

        if (($filters['overdue'] ?? false) || ($filters['sla'] ?? null) === 'overdue') {
            $this->applyOverdueFilter($query);
        }

        if (($filters['sla'] ?? null) === 'first_response_overdue') {
            $query->whereNull('first_responded_at')
                ->where('first_response_due_at', '<', now());
        }

        if (($filters['sla'] ?? null) === 'resolution_overdue') {
            $query->whereNotIn('status', ['resolved', 'closed'])
                ->where('resolution_due_at', '<', now());
        }

        if (($filters['sla'] ?? null) === 'on_track') {
            $query->where(function ($q) {
                $q->whereNotNull('first_responded_at')
                    ->orWhereNull('first_response_due_at')
                    ->orWhere('first_response_due_at', '>=', now());
            })->where(function ($q) {
                $q->whereIn('status', ['resolved', 'closed'])
                    ->orWhereNull('resolution_due_at')
                    ->orWhere('resolution_due_at', '>=', now());
            });
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('assignedTo', function ($assignedQuery) use ($search) {
                        $assignedQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('channel', fn ($channelQuery) => $channelQuery->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('team', fn ($teamQuery) => $teamQuery->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('tags', fn ($tagQuery) => $tagQuery->where('name', 'like', '%'.$search.'%'));
            });
        }

        $sort = $filters['sort_by'] ?? 'created_at';
        $sortDirection = strtolower($filters['sort_direction'] ?? 'desc');
        $allowedSorts = [
            'created_at',
            'updated_at',
            'title',
            'priority',
            'status',
            'first_response_due_at',
            'resolution_due_at',
        ];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
            $sortDirection = 'desc';
        }

        if (! in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        $tickets = $query->orderBy($sort, $sortDirection)->paginate(10);

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

    public function store(
        StoreTicketRequest $request,
        AutomationService $automationService,
        CustomFieldValueService $customFieldValueService,
        IntegrationEventService $integrationEventService
    ) {
        $validated = $request->validated();
        $tagIds = $validated['tag_ids'] ?? [];
        $customFields = $validated['custom_fields'] ?? [];
        unset($validated['tag_ids'], $validated['custom_fields']);

        return DB::transaction(function () use (
            $validated,
            $tagIds,
            $customFields,
            $request,
            $automationService,
            $customFieldValueService,
            $integrationEventService
        ) {
            $ticket = new Ticket([
                ...$validated,
                'user_id' => $request->user()->id,
            ]);
            $ticket->applySlaTargets();
            $ticket->save();

            if ($tagIds !== []) {
                $ticket->tags()->sync($tagIds);
            }

            $customFieldValueService->syncForTicket($ticket, $customFields, true);

            $ticket->recordActivity(
                type: 'ticket_created',
                user: $request->user(),
                description: 'Ticket created'
            );

            $automationService->runForTicket($ticket, 'ticket_created', $request->user());
            $integrationEventService->dispatch('ticket.created', [
                'ticket_id' => $ticket->id,
                'title' => $ticket->title,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
            ]);

            $ticket->load($this->ticketRelations());

            return response()->json([
                'message' => 'Ticket created successfully',
                'ticket' => new TicketResource($ticket),
            ], 201);
        });
    }

    public function show(Request $request, Ticket $ticket)
    {
        if ($request->user()->cannot('view', $ticket)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ticket->load($this->ticketRelations());

        return response()->json([
            'ticket' => new TicketResource($ticket),
        ]);
    }

    public function update(
        UpdateTicketRequest $request,
        Ticket $ticket,
        AutomationService $automationService,
        CustomFieldValueService $customFieldValueService,
        IntegrationEventService $integrationEventService
    ) {
        if ($request->user()->cannot('update', $ticket)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();
        $syncTags = array_key_exists('tag_ids', $validated);
        $tagIds = $validated['tag_ids'] ?? [];
        $syncCustomFields = array_key_exists('custom_fields', $validated);
        $customFields = $validated['custom_fields'] ?? [];
        unset($validated['tag_ids'], $validated['custom_fields']);

        $original = $ticket->only([
            'title',
            'description',
            'status',
            'priority',
            'category_id',
            'channel_id',
            'team_id',
            'assigned_to_id',
            'first_response_due_at',
            'resolution_due_at',
        ]);
        $oldTagIds = $ticket->tags()->pluck('ticket_tags.id')->sort()->values()->all();
        $oldStatus = $ticket->status;
        $oldPriority = $ticket->priority;

        $ticket->fill($validated);

        if (($validated['priority'] ?? null) && $validated['priority'] !== $oldPriority) {
            if (! array_key_exists('first_response_due_at', $validated)) {
                $ticket->first_response_due_at = null;
            }

            if (! array_key_exists('resolution_due_at', $validated)) {
                $ticket->resolution_due_at = null;
            }

            $ticket->applySlaTargets();
        }

        $this->applyLifecycleTimestamps($ticket, $oldStatus);
        $ticket->save();

        if ($syncTags) {
            $ticket->tags()->sync($tagIds);
        }

        if ($syncCustomFields) {
            $customFieldValueService->syncForTicket($ticket, $customFields);
        }

        $changes = collect($ticket->getChanges())
            ->except(['updated_at'])
            ->toArray();

        $newTagIds = $ticket->tags()->pluck('ticket_tags.id')->sort()->values()->all();

        if ($changes !== [] || $oldTagIds !== $newTagIds) {
            $ticket->recordActivity(
                type: 'ticket_updated',
                user: $request->user(),
                description: 'Ticket updated',
                metadata: [
                    'old' => array_intersect_key($original, $changes),
                    'new' => $changes,
                    'old_tag_ids' => $oldTagIds,
                    'new_tag_ids' => $newTagIds,
                ]
            );
        }

        $automationService->runForTicket($ticket, 'ticket_updated', $request->user());
        $integrationEventService->dispatch('ticket.updated', [
            'ticket_id' => $ticket->id,
            'title' => $ticket->title,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
        ]);

        $ticket->load($this->ticketRelations());

        return response()->json([
            'message' => 'Ticket updated successfully',
            'ticket' => new TicketResource($ticket),
        ]);
    }

    public function updateStatus(
        UpdateTicketStatusRequest $request,
        Ticket $ticket,
        AutomationService $automationService,
        IntegrationEventService $integrationEventService
    ) {
        if ($request->user()->cannot('update', $ticket)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $oldStatus = $ticket->status;
        $ticket->status = $request->validated()['status'];
        $this->applyLifecycleTimestamps($ticket, $oldStatus);
        $ticket->save();

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

        $automationService->runForTicket($ticket, 'ticket_updated', $request->user());
        $integrationEventService->dispatch('ticket.status_changed', [
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => $ticket->status,
        ]);

        $ticket->load($this->ticketRelations());

        return response()->json([
            'message' => 'Ticket status updated successfully',
            'ticket' => new TicketResource($ticket),
        ]);
    }

    public function assign(
        AssignTicketRequest $request,
        Ticket $ticket,
        AutomationService $automationService,
        IntegrationEventService $integrationEventService
    ) {
        if ($request->user()->cannot('assign', $ticket)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();
        $oldAssignedToId = $ticket->assigned_to_id;
        $oldTeamId = $ticket->team_id;

        if (array_key_exists('assigned_to_id', $validated)) {
            $ticket->assigned_to_id = $validated['assigned_to_id'];
        }

        if (array_key_exists('team_id', $validated)) {
            $ticket->team_id = $validated['team_id'];
        }

        $ticket->save();

        if ($oldAssignedToId !== $ticket->assigned_to_id || $oldTeamId !== $ticket->team_id) {
            $assignedToChanged = $oldAssignedToId !== $ticket->assigned_to_id;
            $activityType = 'ticket_assignment_updated';
            $description = 'Ticket assignment updated';

            if ($assignedToChanged) {
                $activityType = $ticket->assigned_to_id ? 'ticket_assigned' : 'ticket_unassigned';
                $description = $ticket->assigned_to_id
                    ? 'Ticket assigned to support agent'
                    : 'Ticket unassigned from support agent';
            }

            $ticket->recordActivity(
                type: $activityType,
                user: $request->user(),
                description: $description,
                oldValue: $assignedToChanged && $oldAssignedToId ? (string) $oldAssignedToId : null,
                newValue: $assignedToChanged && $ticket->assigned_to_id ? (string) $ticket->assigned_to_id : null,
                metadata: [
                    'old_assigned_to_id' => $oldAssignedToId,
                    'new_assigned_to_id' => $ticket->assigned_to_id,
                    'old_team_id' => $oldTeamId,
                    'new_team_id' => $ticket->team_id,
                ]
            );

            if ($ticket->assigned_to_id && $ticket->assigned_to_id !== $request->user()->id) {
                $assignedUser = User::find($ticket->assigned_to_id);

                $assignedUser?->notify(new TicketAssignedNotification($ticket));
            }
        }

        $automationService->runForTicket($ticket, 'ticket_updated', $request->user());
        $integrationEventService->dispatch('ticket.assigned', [
            'ticket_id' => $ticket->id,
            'assigned_to_id' => $ticket->assigned_to_id,
            'team_id' => $ticket->team_id,
        ]);

        $ticket->load($this->ticketRelations());

        $message = 'Ticket assignment updated successfully';

        if (array_key_exists('assigned_to_id', $validated)) {
            $message = $ticket->assigned_to_id
                ? 'Ticket assigned successfully'
                : 'Ticket unassigned successfully';
        }

        return response()->json([
            'message' => $message,
            'ticket' => new TicketResource($ticket),
        ]);
    }

    public function destroy(Request $request, Ticket $ticket)
    {
        if ($request->user()->cannot('delete', $ticket)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ticket->delete();

        return response()->json([
            'message' => 'Ticket deleted successfully',
        ]);
    }

    private function ticketRelations(): array
    {
        return ['user', 'assignedTo', 'category', 'channel', 'team', 'tags', 'customFieldValues.customField'];
    }

    private function applyLifecycleTimestamps(Ticket $ticket, string $oldStatus): void
    {
        if ($oldStatus === $ticket->status) {
            return;
        }

        if ($ticket->status === 'resolved' && ! $ticket->resolved_at) {
            $ticket->resolved_at = now();
        }

        if ($ticket->status === 'closed' && ! $ticket->closed_at) {
            $ticket->closed_at = now();
        }

        if ($ticket->status === 'reopened') {
            $ticket->resolved_at = null;
            $ticket->closed_at = null;
        }
    }

    private function applyOverdueFilter($query): void
    {
        $query->where(function ($q) {
            $q->where(function ($firstResponseQuery) {
                $firstResponseQuery->whereNull('first_responded_at')
                    ->where('first_response_due_at', '<', now());
            })->orWhere(function ($resolutionQuery) {
                $resolutionQuery->whereNotIn('status', ['resolved', 'closed'])
                    ->where('resolution_due_at', '<', now());
            });
        });
    }
}
