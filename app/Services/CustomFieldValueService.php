<?php

namespace App\Services;

use App\Models\CustomField;
use App\Models\Ticket;
use Illuminate\Validation\ValidationException;

class CustomFieldValueService
{
    public function syncForTicket(Ticket $ticket, array $values, bool $enforceRequired = false): void
    {
        $fields = CustomField::query()
            ->where('is_active', true)
            ->where(function ($query) use ($ticket) {
                $query->whereNull('category_id');

                if ($ticket->category_id) {
                    $query->orWhere('category_id', $ticket->category_id);
                }
            })
            ->get();

        $normalizedValues = $this->normalizeValues($values, $fields);

        if ($enforceRequired) {
            $missing = $fields
                ->where('is_required', true)
                ->filter(fn (CustomField $field) => ! array_key_exists($field->id, $normalizedValues)
                    || $normalizedValues[$field->id] === null
                    || $normalizedValues[$field->id] === '')
                ->pluck('key')
                ->values()
                ->all();

            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'custom_fields' => 'Missing required custom fields: '.implode(', ', $missing),
                ]);
            }
        }

        foreach ($normalizedValues as $fieldId => $value) {
            $field = $fields->firstWhere('id', $fieldId);

            if (! $field) {
                continue;
            }

            $ticket->customFieldValues()->updateOrCreate(
                ['custom_field_id' => $fieldId],
                ['value' => $this->serializeValue($value)]
            );
        }
    }

    private function normalizeValues(array $values, $fields): array
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            $field = is_numeric($key)
                ? $fields->firstWhere('id', (int) $key)
                : $fields->firstWhere('key', $key);

            if ($field) {
                $normalized[$field->id] = $value;
            }
        }

        return $normalized;
    }

    private function serializeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }
}
