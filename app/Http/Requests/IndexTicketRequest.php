<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'nullable|integer|exists:ticket_categories,id',
            'channel_id' => 'nullable|integer|exists:ticket_channels,id',
            'team_id' => 'nullable|integer|exists:support_teams,id',
            'tag_id' => 'nullable|integer|exists:ticket_tags,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:ticket_tags,id',
            'status' => ['nullable', Rule::in(Ticket::STATUSES)],
            'priority' => ['nullable', Rule::in(Ticket::PRIORITIES)],
            'search' => 'nullable|string|max:255',
            'created_from' => 'nullable|date',
            'created_to' => 'nullable|date',
            'due_before' => 'nullable|date',
            'overdue' => 'nullable|boolean',
            'sla' => 'nullable|string|in:on_track,overdue,first_response_overdue,resolution_overdue',
            'sort_by' => 'nullable|string',
            'sort_direction' => 'nullable|string',
            'assigned' => 'nullable|string|in:me,unassigned',
            'assigned_to_id' => 'nullable|integer|exists:users,id',
        ];
    }
}
