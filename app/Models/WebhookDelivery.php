<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_id',
        'event',
        'payload',
        'status',
        'response_status',
        'response_body',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempted_at' => 'datetime',
        ];
    }

    public function webhook()
    {
        return $this->belongsTo(Webhook::class);
    }
}
