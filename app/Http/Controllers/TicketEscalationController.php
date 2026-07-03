<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveTicketEscalationRequest;
use App\Http\Requests\StoreTicketEscalationRequest;
use App\Http\Resources\TicketEscalationResource;
use App\Models\Ticket;
use App\Models\TicketEscalation;
use Illuminate\Http\Request;

class TicketEscalationController extends Controller
{
    public function index(Request $request, Ticket $ticket)
    {
        if (! $request->user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $escalations = $ticket->escalations()
            ->with(['escalatedBy', 'escalatedTo', 'resolvedBy', 'team'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'escalations' => TicketEscalationResource::collection($escalations),
            'pagination' => [
                'total' => $escalations->total(),
                'per_page' => $escalations->perPage(),
                'current_page' => $escalations->currentPage(),
                'last_page' => $escalations->lastPage(),
                'from' => $escalations->firstItem(),
                'to' => $escalations->lastItem(),
            ],
        ]);
    }

    public function store(StoreTicketEscalationRequest $request, Ticket $ticket)
    {
        if (! $request->user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();

        $escalation = $ticket->escalations()->create([
            'escalated_by_id' => $request->user()->id,
            'escalated_to_id' => $validated['escalated_to_id'] ?? null,
            'team_id' => $validated['team_id'] ?? null,
            'from_priority' => $ticket->priority,
            'to_priority' => $validated['to_priority'] ?? $ticket->priority,
            'reason' => $validated['reason'] ?? null,
            'status' => 'open',
        ]);

        $ticket->forceFill([
            'priority' => $validated['to_priority'] ?? $ticket->priority,
            'assigned_to_id' => $validated['escalated_to_id'] ?? $ticket->assigned_to_id,
            'team_id' => $validated['team_id'] ?? $ticket->team_id,
        ]);
        $ticket->first_response_due_at = null;
        $ticket->resolution_due_at = null;
        $ticket->applySlaTargets();
        $ticket->save();

        $ticket->recordActivity(
            type: 'ticket_escalated',
            user: $request->user(),
            description: 'Ticket escalated',
            metadata: [
                'escalation_id' => $escalation->id,
                'to_priority' => $escalation->to_priority,
                'escalated_to_id' => $escalation->escalated_to_id,
                'team_id' => $escalation->team_id,
            ]
        );

        $escalation->load(['escalatedBy', 'escalatedTo', 'resolvedBy', 'team']);

        return response()->json([
            'message' => 'Ticket escalated successfully',
            'escalation' => new TicketEscalationResource($escalation),
        ], 201);
    }

    public function resolve(
        ResolveTicketEscalationRequest $request,
        Ticket $ticket,
        TicketEscalation $escalation
    ) {
        if (! $request->user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($escalation->ticket_id !== $ticket->id) {
            return response()->json(['message' => 'Escalation not found for this ticket'], 404);
        }

        $escalation->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by_id' => $request->user()->id,
        ]);

        $ticket->recordActivity(
            type: 'ticket_escalation_resolved',
            user: $request->user(),
            description: 'Ticket escalation resolved',
            metadata: [
                'escalation_id' => $escalation->id,
                'resolution_note' => $request->validated()['resolution_note'] ?? null,
            ]
        );

        $escalation->load(['escalatedBy', 'escalatedTo', 'resolvedBy', 'team']);

        return response()->json([
            'message' => 'Ticket escalation resolved successfully',
            'escalation' => new TicketEscalationResource($escalation),
        ]);
    }
}
