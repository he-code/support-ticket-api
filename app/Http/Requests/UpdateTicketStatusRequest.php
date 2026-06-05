<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
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
        'status' => 'sometimes|in:open,in_progress,resolved,closed',
        'priority' => 'sometimes|in:low,medium,high',
    ];
}
}
