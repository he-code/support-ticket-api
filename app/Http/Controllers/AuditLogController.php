<?php

namespace App\Http\Controllers;

use App\Http\Resources\TicketActivityResource;
use App\Models\TicketActivity;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = TicketActivity::query()
            ->with(['ticket', 'user'])
            ->latest();

        if (! $request->user()->isStaff()) {
            $query->whereHas('ticket', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        }

        if ($request->filled('ticket_id')) {
            $query->where('ticket_id', $request->integer('ticket_id'));
        }

        if ($request->filled('user_id') && $request->user()->isAdmin()) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', '%'.$search.'%')
                    ->orWhere('type', 'like', '%'.$search.'%')
                    ->orWhereHas('ticket', fn ($ticketQuery) => $ticketQuery->where('title', 'like', '%'.$search.'%'));
            });
        }

        $activities = $query->paginate(20);

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
