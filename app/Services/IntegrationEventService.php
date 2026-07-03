<?php

namespace App\Services;

use App\Models\Webhook;

class IntegrationEventService
{
    public function dispatch(string $event, array $payload): void
    {
        Webhook::query()
            ->where('is_active', true)
            ->get()
            ->filter(fn (Webhook $webhook) => in_array($event, $webhook->events ?? [], true)
                || in_array('*', $webhook->events ?? [], true))
            ->each(function (Webhook $webhook) use ($event, $payload) {
                $webhook->deliveries()->create([
                    'event' => $event,
                    'payload' => $payload,
                    'status' => 'pending',
                ]);
            });
    }
}
