<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketCommentRequest;
use App\Http\Resources\TicketCommentResource;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Notifications\TicketCommentCreatedNotification;
use Illuminate\Http\Request;

class TicketCommentController extends Controller
{
    public function index(Request $request, Ticket $ticket)
    {
        if (! $request->user()->isStaff() && $ticket->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $comments = $ticket->comments()
            ->with('user')
            ->oldest()
            ->paginate(10);

        return response()->json([
            'comments' => TicketCommentResource::collection($comments),
            'pagination' => [
                'total' => $comments->total(),
                'per_page' => $comments->perPage(),
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'from' => $comments->firstItem(),
                'to' => $comments->lastItem(),
            ],
        ]);
    }

    public function store(StoreTicketCommentRequest $request, Ticket $ticket)
    {
        if (! $request->user()->isStaff() && $ticket->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $comment = $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated()['body'],
        ]);

        $ticket->recordActivity(
            type: 'comment_created',
            user: $request->user(),
            description: 'Comment added to ticket',
            metadata: [
                'comment_id' => $comment->id,
            ]
        );
        // Si comenta un agente o admin, notificamos al dueño del ticket.
        // Si comenta el dueño del ticket, notificamos al agente asignado.
        // Nunca notificamos al mismo usuario que creó el comentario.
        $ticket->loadMissing(['user', 'assignedTo']);

        if ($ticket->user_id !== $request->user()->id) {
            $ticket->user?->notify(new TicketCommentCreatedNotification($ticket));
        } elseif (
            $ticket->assigned_to_id
            && $ticket->assigned_to_id !== $request->user()->id
        ) {
            $ticket->assignedTo?->notify(new TicketCommentCreatedNotification($ticket));
        }

        $comment->load('user');

        return response()->json([
            'message' => 'Comment created successfully',
            'comment' => new TicketCommentResource($comment),
        ], 201);
    }

    public function destroy(Request $request, Ticket $ticket, TicketComment $comment)
    {
        if ($comment->ticket_id !== $ticket->id) {
            return response()->json([
                'message' => 'Comment not found for this ticket',
            ], 404);
        }

        if (
            ! $request->user()->isAdmin()
            && $comment->user_id !== $request->user()->id
        ) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $commentId = $comment->id;

        $comment->delete();

        $ticket->recordActivity(
            type: 'comment_deleted',
            user: $request->user(),
            description: 'Comment deleted from ticket',
            metadata: [
                'comment_id' => $commentId,
            ]
        );

        return response()->json([
            'message' => 'Comment deleted successfully',
        ]);
    }
}
