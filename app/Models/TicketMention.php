<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketMention extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'mentioned_user_id',
        'mentioned_by_id',
        'source_type',
        'source_id',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function mentionedUser()
    {
        return $this->belongsTo(User::class, 'mentioned_user_id');
    }

    public function mentionedBy()
    {
        return $this->belongsTo(User::class, 'mentioned_by_id');
    }
}
