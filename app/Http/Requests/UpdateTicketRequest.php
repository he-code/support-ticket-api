<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => ['sometimes', Rule::in(Ticket::STATUSES)],
            'priority' => ['sometimes', Rule::in(Ticket::PRIORITIES)],
            'category_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('ticket_categories', 'id')->where('is_active', true),
            ],
            'channel_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('ticket_channels', 'id')->where('is_active', true),
            ],
            'team_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('support_teams', 'id')->where('is_active', true),
            ],
            'tag_ids' => 'sometimes|array',
            'tag_ids.*' => [
                'integer',
                Rule::exists('ticket_tags', 'id')->where('is_active', true),
            ],
            'first_response_due_at' => 'sometimes|nullable|date',
            'resolution_due_at' => 'sometimes|nullable|date',
            'custom_fields' => 'sometimes|array',
        ];
    }
}
