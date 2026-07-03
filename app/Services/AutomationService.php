<?php

namespace App\Services;

use App\Models\AutomationRule;
use App\Models\Ticket;
use App\Models\User;

class AutomationService
{
    public function runForTicket(Ticket $ticket, string $event, ?User $actor = null): void
    {
        $rules = AutomationRule::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            if (! $this->matches($ticket, $event, $rule->conditions ?? [])) {
                continue;
            }

            $this->applyActions($ticket, $rule->actions ?? [], $rule->name, $actor);
        }
    }

    private function matches(Ticket $ticket, string $event, array $conditions): bool
    {
        if (($conditions['event'] ?? null) && $conditions['event'] !== $event) {
            return false;
        }

        foreach ($conditions as $field => $expected) {
            if ($field === 'event') {
                continue;
            }

            if ($field === 'tag_id') {
                if (! $ticket->tags()->whereKey($expected)->exists()) {
                    return false;
                }

                continue;
            }

            if ($ticket->getAttribute($field) != $expected) {
                return false;
            }
        }

        return true;
    }

    private function applyActions(Ticket $ticket, array $actions, string $ruleName, ?User $actor): void
    {
        $updates = collect($actions)
            ->only(['assigned_to_id', 'team_id', 'priority', 'status'])
            ->filter(fn ($value) => $value !== '')
            ->toArray();

        if ($updates !== []) {
            $ticket->forceFill($updates);

            if (array_key_exists('priority', $updates)) {
                $ticket->first_response_due_at = null;
                $ticket->resolution_due_at = null;
                $ticket->applySlaTargets();
            }

            if (($updates['status'] ?? null) === 'resolved' && ! $ticket->resolved_at) {
                $ticket->resolved_at = now();
            }

            if (($updates['status'] ?? null) === 'closed' && ! $ticket->closed_at) {
                $ticket->closed_at = now();
            }

            $ticket->save();
        }

        if (! empty($actions['tag_ids']) && is_array($actions['tag_ids'])) {
            $ticket->tags()->syncWithoutDetaching($actions['tag_ids']);
        }

        if ($updates !== [] || ! empty($actions['tag_ids'])) {
            $ticket->recordActivity(
                type: 'automation_applied',
                user: $actor,
                description: 'Automation rule applied',
                metadata: [
                    'rule' => $ruleName,
                    'actions' => $actions,
                ]
            );
        }
    }
}
