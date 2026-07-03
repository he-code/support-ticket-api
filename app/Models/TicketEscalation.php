<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketEscalation extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'escalated_by_id',
        'escalated_to_id',
        'team_id',
        'from_priority',
        'to_priority',
        'status',
        'reason',
        'resolved_at',
        'resolved_by_id',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function escalatedBy()
    {
        return $this->belongsTo(User::class, 'escalated_by_id');
    }

    public function escalatedTo()
    {
        return $this->belongsTo(User::class, 'escalated_to_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }

    public function team()
    {
        return $this->belongsTo(SupportTeam::class, 'team_id');
    }
}
