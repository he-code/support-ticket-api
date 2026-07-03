<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessHourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'day_of_week' => 'required|integer|min:0|max:6',
            'opens_at' => 'nullable|date_format:H:i',
            'closes_at' => 'nullable|date_format:H:i|after:opens_at',
            'is_open' => 'nullable|boolean',
        ];
    }
}
