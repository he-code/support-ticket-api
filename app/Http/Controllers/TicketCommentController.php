<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketCommentRequest;
use App\Http\Resources\TicketCommentResource;
use App\Models\Ticket;
use App\Models\TicketComment;
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

    $comment->delete();

    return response()->json([
        'message' => 'Comment deleted successfully',
    ]);
    }
}