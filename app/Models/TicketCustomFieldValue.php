<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketCustomFieldValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'custom_field_id',
        'value',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function customField()
    {
        return $this->belongsTo(CustomField::class);
    }
}
