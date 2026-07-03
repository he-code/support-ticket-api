<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'is_internal' => $this->is_internal,
            'metadata' => $this->metadata,
            'is_previewable' => $this->isPreviewable(),
            'download_url' => url("/api/tickets/{$this->ticket_id}/attachments/{$this->id}/download"),
            'preview_url' => $this->isPreviewable()
                ? url("/api/tickets/{$this->ticket_id}/attachments/{$this->id}/preview")
                : null,
            'uploaded_by' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ] : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
