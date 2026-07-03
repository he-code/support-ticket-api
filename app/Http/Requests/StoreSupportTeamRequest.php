<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:support_teams,name',
            'description' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'member_ids' => 'nullable|array',
            'member_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('role', ['support_agent', 'admin']);
                }),
            ],
        ];
    }
}
