<?php

namespace App\Http\Requests;

use App\Models\AutomationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAutomationRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rule = $this->route('automation_rule') ?? $this->route('automationRule');
        $ruleId = $rule instanceof AutomationRule ? $rule->id : $rule;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('automation_rules', 'name')->ignore($ruleId),
            ],
            'description' => 'nullable|string|max:255',
            'conditions' => 'sometimes|required|array',
            'actions' => 'sometimes|required|array',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:1|max:1000',
        ];
    }
}
