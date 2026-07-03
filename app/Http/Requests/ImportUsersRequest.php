<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ImportUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Unauthorized',
        ], 403));
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|max:10240|mimes:csv,txt,xlsx',
            'update_existing' => 'nullable|boolean',
            'default_password' => 'nullable|string|min:6',
        ];
    }
}
