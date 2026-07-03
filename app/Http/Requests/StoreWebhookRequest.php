<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:1000',
            'secret' => 'nullable|string|max:255',
            'events' => 'required|array|min:1',
            'events.*' => 'string|max:100',
            'is_active' => 'nullable|boolean',
        ];
    }
}
