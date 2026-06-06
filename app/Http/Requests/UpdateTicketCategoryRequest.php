<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'nullable|integer|exists:ticket_categories,id',
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('ticket_categories', 'name')->ignore($this->route('ticketCategory')->id),
            ],
            'description' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            
        ];
    }
}