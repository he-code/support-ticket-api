<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAutomationRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:automation_rules,name',
            'description' => 'nullable|string|max:255',
            'conditions' => 'required|array',
            'actions' => 'required|array',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:1|max:1000',
        ];
    }
}
