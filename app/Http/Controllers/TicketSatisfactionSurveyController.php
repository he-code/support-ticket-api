<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketSatisfactionSurveyRequest;
use App\Http\Resources\TicketSatisfactionSurveyResource;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketSatisfactionSurveyController extends Controller
{
    public function show(Request $request, Ticket $ticket)
    {
        if ($request->user()->cannot('view', $ticket)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $survey = $ticket->satisfactionSurvey()->with('user')->first();

        if (! $survey) {
            return response()->json(['message' => 'Satisfaction survey not found'], 404);
        }

        return response()->json([
            'survey' => new TicketSatisfactionSurveyResource($survey),
        ]);
    }

    public function store(StoreTicketSatisfactionSurveyRequest $request, Ticket $ticket)
    {
        if ($ticket->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (! in_array($ticket->status, ['resolved', 'closed'], true)) {
            return response()->json([
                'message' => 'Only resolved or closed tickets can be rated',
            ], 422);
        }

        $survey = $ticket->satisfactionSurvey()->updateOrCreate(
            ['ticket_id' => $ticket->id],
            [
                ...$request->validated(),
                'user_id' => $request->user()->id,
            ]
        );

        $ticket->recordActivity(
            type: 'satisfaction_submitted',
            user: $request->user(),
            description: 'Satisfaction survey submitted',
            metadata: [
                'rating' => $survey->rating,
            ]
        );

        $survey->load('user');

        return response()->json([
            'message' => 'Satisfaction survey submitted successfully',
            'survey' => new TicketSatisfactionSurveyResource($survey),
        ], 201);
    }
}
