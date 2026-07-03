<?php

namespace App\Http\Requests;

use App\Models\TicketTag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tag = $this->route('ticket_tag') ?? $this->route('ticketTag');
        $tagId = $tag instanceof TicketTag ? $tag->id : $tag;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('ticket_tags', 'name')->ignore($tagId),
            ],
            'color' => ['nullable', 'string', 'max:20', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'is_active' => 'nullable|boolean',
        ];
    }
}
