<?php

namespace App\Http\Requests;

use App\Models\TicketCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // En rutas resource con nombre "ticket-categories",
        // Laravel normalmente usa el parámetro "ticket_category".
        // Dejamos también "ticketCategory" como respaldo por si luego personalizas el nombre.
        $ticketCategory = $this->route('ticket_category') ?? $this->route('ticketCategory');

        // Si el parámetro ya fue resuelto por Route Model Binding, tomamos su id.
        // Si no, usamos directamente el valor recibido.
        $ticketCategoryId = $ticketCategory instanceof TicketCategory
            ? $ticketCategory->id
            : $ticketCategory;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('ticket_categories', 'name')->ignore($ticketCategoryId),
            ],
            'description' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }
}
