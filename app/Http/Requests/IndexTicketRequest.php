<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|in:open,in_progress,resolved,closed',
            'priority' => 'nullable|in:low,medium,high',
            'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|string|max:50',
            'sort_direction' => 'nullable|string|max:50',
        ];
    }
}