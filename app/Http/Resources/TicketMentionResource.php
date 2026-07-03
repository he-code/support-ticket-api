<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketMentionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'mentioned_user' => $this->mentionedUser ? [
                'id' => $this->mentionedUser->id,
                'name' => $this->mentionedUser->name,
                'email' => $this->mentionedUser->email,
            ] : null,
            'mentioned_by' => $this->mentionedBy ? [
                'id' => $this->mentionedBy->id,
                'name' => $this->mentionedBy->name,
                'email' => $this->mentionedBy->email,
            ] : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
