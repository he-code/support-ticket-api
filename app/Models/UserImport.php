<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by_id',
        'original_name',
        'created_count',
        'updated_count',
        'skipped_count',
        'errors',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
        ];
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
