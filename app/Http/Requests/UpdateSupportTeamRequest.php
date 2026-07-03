<?php

namespace App\Http\Requests;

use App\Models\SupportTeam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupportTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $team = $this->route('support_team') ?? $this->route('supportTeam');
        $teamId = $team instanceof SupportTeam ? $team->id : $team;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('support_teams', 'name')->ignore($teamId),
            ],
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
