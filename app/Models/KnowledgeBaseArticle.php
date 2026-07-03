<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeBaseArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'created_by_id',
        'title',
        'slug',
        'summary',
        'content',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function category()
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function tickets()
    {
        return $this->belongsToMany(Ticket::class, 'knowledge_base_article_ticket')
            ->withPivot('attached_by_id')
            ->withTimestamps();
    }
}
