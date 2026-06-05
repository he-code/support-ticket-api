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

        'created_at' => $this->created_at->format('Y-m-d H:i:s')
    ];
}
}
