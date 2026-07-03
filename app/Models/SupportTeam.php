<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'support_team_user')->withTimestamps();
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'team_id');
    }
}
