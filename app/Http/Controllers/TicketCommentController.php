<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketCommentRequest;
use App\Http\Resources\TicketCommentResource;
use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Http\Request;
use App\Notifications\TicketCommentCreatedNotification;

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
        // Notificar al propietario del ticket si el comentario fue creado por un agente
        $ticket->loadMissing('user');

        if ($ticket->user_id !== $request->user()->id) {
            $ticket->user?->notify(new TicketCommentCreatedNotification($ticket));
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