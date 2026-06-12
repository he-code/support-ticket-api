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

            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'description' => $this->category->description,
            ] : null,
        ];
    }
}
