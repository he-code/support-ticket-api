<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $baseQuery = Ticket::query();

        if (! $request->user()->isStaff()) {
            $baseQuery->where('user_id', $request->user()->id);
        }

        return response()->json([
            'total_tickets' => (clone $baseQuery)->count(),

            'by_status' => collect(Ticket::STATUSES)
                ->mapWithKeys(fn ($status) => [
                    $status => (clone $baseQuery)->where('status', $status)->count(),
                ])
                ->toArray(),

            'by_priority' => collect(Ticket::PRIORITIES)
                ->mapWithKeys(fn ($priority) => [
                    $priority => (clone $baseQuery)->where('priority', $priority)->count(),
                ])
                ->toArray(),

            'unassigned_tickets' => (clone $baseQuery)
                ->whereNull('assigned_to_id')
                ->count(),

            'assigned_to_me' => Ticket::query()
                ->where('assigned_to_id', $request->user()->id)
                ->count(),

            'sla' => [
                'first_response_overdue' => (clone $baseQuery)
                    ->whereNull('first_responded_at')
                    ->where('first_response_due_at', '<', now())
                    ->count(),
                'resolution_overdue' => (clone $baseQuery)
                    ->whereNotIn('status', ['resolved', 'closed'])
                    ->where('resolution_due_at', '<', now())
                    ->count(),
            ],
        ]);
    }
}
