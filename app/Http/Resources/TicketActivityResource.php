<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'description' => $this->description,
            'old_value' => $this->old_value,
            'new_value' => $this->new_value,
            'metadata' => $this->metadata,

            'performed_by' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ] : null,

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
