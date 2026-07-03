<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:ticket_tags,name',
            'color' => ['nullable', 'string', 'max:20', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'is_active' => 'nullable|boolean',
        ];
    }
}
