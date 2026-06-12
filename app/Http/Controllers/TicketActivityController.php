<?php

namespace App\Http\Controllers;

use App\Http\Resources\TicketActivityResource;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketActivityController extends Controller
{
    public function index(Request $request, Ticket $ticket)
    {
        // Solo puede ver el historial quien puede ver el ticket
        if ($request->user()->cannot('view', $ticket)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Timeline ordenado desde la actividad más reciente
        $activities = $ticket->activities()
            ->with('user')
            ->latest()
            ->paginate(10);

        return response()->json([
            'activities' => TicketActivityResource::collection($activities),
            'pagination' => [
                'total' => $activities->total(),
                'per_page' => $activities->perPage(),
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'from' => $activities->firstItem(),
                'to' => $activities->lastItem(),
            ],
        ]);
    }
}
