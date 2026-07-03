<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketEscalationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'reason' => $this->reason,
            'from_priority' => $this->from_priority,
            'to_priority' => $this->to_priority,
            'team' => $this->team ? [
                'id' => $this->team->id,
                'name' => $this->team->name,
            ] : null,
            'escalated_to' => $this->escalatedTo ? [
                'id' => $this->escalatedTo->id,
                'name' => $this->escalatedTo->name,
                'email' => $this->escalatedTo->email,
            ] : null,
            'escalated_by' => $this->escalatedBy ? [
                'id' => $this->escalatedBy->id,
                'name' => $this->escalatedBy->name,
                'email' => $this->escalatedBy->email,
            ] : null,
            'resolved_by' => $this->resolvedBy ? [
                'id' => $this->resolvedBy->id,
                'name' => $this->resolvedBy->name,
                'email' => $this->resolvedBy->email,
            ] : null,
            'resolved_at' => $this->resolved_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
