<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'original_name',
        'file_path',
        'mime_type',
        'size',
        'is_internal',
        'preview_path',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function isPreviewable(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/')
            || in_array($this->mime_type, ['application/pdf', 'text/plain'], true);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
