<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketInternalNoteRequest;
use App\Http\Resources\TicketInternalNoteResource;
use App\Models\Ticket;
use App\Models\TicketInternalNote;
use App\Services\MentionService;
use Illuminate\Http\Request;

class TicketInternalNoteController extends Controller
{
    public function index(Request $request, Ticket $ticket)
    {
        if (! $request->user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notes = $ticket->internalNotes()
            ->with('user')
            ->latest()
            ->paginate(10);

        return response()->json([
            'internal_notes' => TicketInternalNoteResource::collection($notes),
            'pagination' => [
                'total' => $notes->total(),
                'per_page' => $notes->perPage(),
                'current_page' => $notes->currentPage(),
                'last_page' => $notes->lastPage(),
                'from' => $notes->firstItem(),
                'to' => $notes->lastItem(),
            ],
        ]);
    }

    public function store(StoreTicketInternalNoteRequest $request, Ticket $ticket, MentionService $mentionService)
    {
        if (! $request->user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();

        $note = $ticket->internalNotes()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        $ticket->recordActivity(
            type: 'internal_note_created',
            user: $request->user(),
            description: 'Internal note added to ticket',
            metadata: ['internal_note_id' => $note->id]
        );

        $mentionService->createMentions(
            $ticket,
            $validated['mention_user_ids'] ?? [],
            $request->user(),
            TicketInternalNote::class,
            $note->id
        );

        $note->load('user');

        return response()->json([
            'message' => 'Internal note created successfully',
            'internal_note' => new TicketInternalNoteResource($note),
        ], 201);
    }

    public function destroy(Request $request, Ticket $ticket, TicketInternalNote $internalNote)
    {
        if (! $request->user()->isAdmin() && $internalNote->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($internalNote->ticket_id !== $ticket->id) {
            return response()->json(['message' => 'Internal note not found for this ticket'], 404);
        }

        $noteId = $internalNote->id;
        $internalNote->delete();

        $ticket->recordActivity(
            type: 'internal_note_deleted',
            user: $request->user(),
            description: 'Internal note deleted from ticket',
            metadata: ['internal_note_id' => $noteId]
        );

        return response()->json([
            'message' => 'Internal note deleted successfully',
        ]);
    }
}
