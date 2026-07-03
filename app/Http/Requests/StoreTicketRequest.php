<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => ['required', Rule::in(Ticket::PRIORITIES)],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('ticket_categories', 'id')->where('is_active', true),
            ],
            'channel_id' => [
                'nullable',
                'integer',
                Rule::exists('ticket_channels', 'id')->where('is_active', true),
            ],
            'team_id' => [
                'nullable',
                'integer',
                Rule::exists('support_teams', 'id')->where('is_active', true),
            ],
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => [
                'integer',
                Rule::exists('ticket_tags', 'id')->where('is_active', true),
            ],
            'first_response_due_at' => 'nullable|date',
            'resolution_due_at' => 'nullable|date',
            'custom_fields' => 'nullable|array',
        ];
    }
}
