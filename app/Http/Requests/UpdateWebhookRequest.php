<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'url' => 'sometimes|required|url|max:1000',
            'secret' => 'nullable|string|max:255',
            'events' => 'sometimes|required|array|min:1',
            'events.*' => 'string|max:100',
            'is_active' => 'nullable|boolean',
        ];
    }
}
