<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use HasFactory;

    public const TYPES = ['text', 'textarea', 'number', 'date', 'select', 'boolean'];

    protected $fillable = [
        'category_id',
        'name',
        'key',
        'type',
        'options',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category()
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function values()
    {
        return $this->hasMany(TicketCustomFieldValue::class);
    }
}
