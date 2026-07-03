<?php

namespace App\Http\Requests;

use App\Models\CustomField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'nullable|integer|exists:ticket_categories,id',
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:100|alpha_dash|unique:custom_fields,key',
            'type' => ['required', Rule::in(CustomField::TYPES)],
            'options' => 'nullable|array',
            'is_required' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
