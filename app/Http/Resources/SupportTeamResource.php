<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'members' => UserResource::collection($this->whenLoaded('members')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
