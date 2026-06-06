<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'user_id',
        'assigned_to_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class);
    }

    public function activities()
    {
        return $this->hasMany(TicketActivity::class);
    }

    public function recordActivity(
    string $type,
    ?User $user = null,
    ?string $description = null,
    ?string $oldValue = null,
    ?string $newValue = null,
    ?array $metadata = null
    ): TicketActivity {
    return $this->activities()->create([
        'user_id' => $user?->id,
        'type' => $type,
        'description' => $description,
        'old_value' => $oldValue,
        'new_value' => $newValue,
        'metadata' => $metadata,
    ]);
    }

}