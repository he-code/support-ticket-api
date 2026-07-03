<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketEscalationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'nullable|string|max:1000',
            'to_priority' => ['nullable', Rule::in(Ticket::PRIORITIES)],
            'escalated_to_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('role', ['support_agent', 'admin']);
                }),
            ],
            'team_id' => [
                'nullable',
                'integer',
                Rule::exists('support_teams', 'id')->where('is_active', true),
            ],
        ];
    }
}
