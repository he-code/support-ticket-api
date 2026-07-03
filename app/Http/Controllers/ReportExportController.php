<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function tickets(Request $request): StreamedResponse
    {
        if (! $request->user()->isStaff()) {
            abort(403, 'Unauthorized');
        }

        $filters = $request->validate([
            'status' => 'nullable|string',
            'priority' => 'nullable|string',
            'category_id' => 'nullable|integer|exists:ticket_categories,id',
            'team_id' => 'nullable|integer|exists:support_teams,id',
            'channel_id' => 'nullable|integer|exists:ticket_channels,id',
            'created_from' => 'nullable|date',
            'created_to' => 'nullable|date',
        ]);

        $query = Ticket::query()->with(['user', 'assignedTo', 'category', 'team', 'channel']);

        foreach (['status', 'priority', 'category_id', 'team_id', 'channel_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'id',
                'title',
                'status',
                'priority',
                'category',
                'team',
                'channel',
                'created_by',
                'assigned_to',
                'created_at',
                'first_response_due_at',
                'resolution_due_at',
            ]);

            $query->orderBy('created_at')->chunk(100, function ($tickets) use ($handle) {
                foreach ($tickets as $ticket) {
                    fputcsv($handle, [
                        $ticket->id,
                        $ticket->title,
                        $ticket->status,
                        $ticket->priority,
                        $ticket->category?->name,
                        $ticket->team?->name,
                        $ticket->channel?->name,
                        $ticket->user?->email,
                        $ticket->assignedTo?->email,
                        $ticket->created_at?->format('Y-m-d H:i:s'),
                        $ticket->first_response_due_at?->format('Y-m-d H:i:s'),
                        $ticket->resolution_due_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, 'tickets-report.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
