<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => 'required|string|min:2|max:5000',
            'mention_user_ids' => 'nullable|array',
            'mention_user_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('role', ['support_agent', 'admin']);
                }),
            ],
        ];
    }
}
