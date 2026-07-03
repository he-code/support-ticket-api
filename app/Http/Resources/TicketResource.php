<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,

            'created_by' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],

            'assigned_to' => $this->assignedTo ? [
                'id' => $this->assignedTo->id,
                'name' => $this->assignedTo->name,
                'email' => $this->assignedTo->email,
            ] : null,

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),

            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'description' => $this->category->description,
            ] : null,

            'channel' => $this->channel ? [
                'id' => $this->channel->id,
                'name' => $this->channel->name,
                'key' => $this->channel->key,
            ] : null,

            'team' => $this->team ? [
                'id' => $this->team->id,
                'name' => $this->team->name,
            ] : null,

            'tags' => TicketTagResource::collection($this->whenLoaded('tags')),

            'custom_fields' => $this->whenLoaded('customFieldValues', function () {
                return $this->customFieldValues->map(fn ($value) => [
                    'id' => $value->customField?->id,
                    'key' => $value->customField?->key,
                    'name' => $value->customField?->name,
                    'type' => $value->customField?->type,
                    'value' => $value->value,
                ])->values();
            }),

            'sla' => [
                'first_response_due_at' => $this->first_response_due_at?->format('Y-m-d H:i:s'),
                'resolution_due_at' => $this->resolution_due_at?->format('Y-m-d H:i:s'),
                'first_responded_at' => $this->first_responded_at?->format('Y-m-d H:i:s'),
                'resolved_at' => $this->resolved_at?->format('Y-m-d H:i:s'),
                'closed_at' => $this->closed_at?->format('Y-m-d H:i:s'),
                'first_response_overdue' => $this->hasFirstResponseOverdue(),
                'resolution_overdue' => $this->hasResolutionOverdue(),
            ],
        ];
    }
}
