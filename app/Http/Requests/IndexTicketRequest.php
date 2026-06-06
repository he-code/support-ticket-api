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
        'category_id' => 'nullable|integer|exists:ticket_categories,id',
        'status' => 'nullable|in:open,in_progress,resolved,closed',
        'priority' => 'nullable|in:low,medium,high',
        'search' => 'nullable|string|max:255',

        // No usamos "in" aquí porque tus tests esperan fallback, no 422
        'sort_by' => 'nullable|string',
        'sort_direction' => 'nullable|string',

        'assigned' => 'nullable|string|in:me,unassigned',
        'assigned_to_id' => 'nullable|integer|exists:users,id',
    ];
    }
}