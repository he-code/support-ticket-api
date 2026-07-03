<?php

namespace App\Http\Controllers;

use App\Http\Resources\TicketMentionResource;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketMentionController extends Controller
{
    public function index(Request $request, Ticket $ticket)
    {
        if (! $request->user()->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $mentions = $ticket->mentions()
            ->with(['mentionedUser', 'mentionedBy'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'mentions' => TicketMentionResource::collection($mentions),
            'pagination' => [
                'total' => $mentions->total(),
                'per_page' => $mentions->perPage(),
                'current_page' => $mentions->currentPage(),
                'last_page' => $mentions->lastPage(),
                'from' => $mentions->firstItem(),
                'to' => $mentions->lastItem(),
            ],
        ]);
    }
}
