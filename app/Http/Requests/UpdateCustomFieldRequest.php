<?php

namespace App\Http\Requests;

use App\Models\CustomField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $field = $this->route('custom_field') ?? $this->route('customField');
        $fieldId = $field instanceof CustomField ? $field->id : $field;

        return [
            'category_id' => 'nullable|integer|exists:ticket_categories,id',
            'name' => 'sometimes|required|string|max:255',
            'key' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('custom_fields', 'key')->ignore($fieldId),
            ],
            'type' => ['sometimes', 'required', Rule::in(CustomField::TYPES)],
            'options' => 'nullable|array',
            'is_required' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
