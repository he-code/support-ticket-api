<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    public const STATUSES = [
        'open',
        'in_progress',
        'waiting_customer',
        'waiting_internal',
        'resolved',
        'closed',
        'reopened',
    ];

    public const PRIORITIES = [
        'low',
        'medium',
        'high',
        'urgent',
    ];

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'user_id',
        'category_id',
        'channel_id',
        'team_id',
        'assigned_to_id',
        'first_response_due_at',
        'resolution_due_at',
        'first_responded_at',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'first_response_due_at' => 'datetime',
            'resolution_due_at' => 'datetime',
            'first_responded_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

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

    public function category()
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function channel()
    {
        return $this->belongsTo(TicketChannel::class, 'channel_id');
    }

    public function team()
    {
        return $this->belongsTo(SupportTeam::class, 'team_id');
    }

    public function tags()
    {
        return $this->belongsToMany(TicketTag::class, 'ticket_tag')->withTimestamps();
    }

    public function activities()
    {
        return $this->hasMany(TicketActivity::class);
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function internalNotes()
    {
        return $this->hasMany(TicketInternalNote::class);
    }

    public function mentions()
    {
        return $this->hasMany(TicketMention::class);
    }

    public function escalations()
    {
        return $this->hasMany(TicketEscalation::class);
    }

    public function customFieldValues()
    {
        return $this->hasMany(TicketCustomFieldValue::class);
    }

    public function knowledgeBaseArticles()
    {
        return $this->belongsToMany(KnowledgeBaseArticle::class, 'knowledge_base_article_ticket')
            ->withPivot('attached_by_id')
            ->withTimestamps();
    }

    public function satisfactionSurvey()
    {
        return $this->hasOne(TicketSatisfactionSurvey::class);
    }

    public function applySlaTargets(): void
    {
        $targets = self::slaTargetsForPriority($this->priority);

        if (! $this->first_response_due_at) {
            $this->first_response_due_at = BusinessHour::addBusinessMinutes(
                now(),
                $targets['first_response_minutes']
            );
        }

        if (! $this->resolution_due_at) {
            $this->resolution_due_at = BusinessHour::addBusinessMinutes(
                now(),
                $targets['resolution_minutes']
            );
        }
    }

    public static function slaTargetsForPriority(string $priority): array
    {
        return match ($priority) {
            'urgent' => [
                'first_response_minutes' => 30,
                'resolution_minutes' => 240,
            ],
            'high' => [
                'first_response_minutes' => 120,
                'resolution_minutes' => 1440,
            ],
            'low' => [
                'first_response_minutes' => 1440,
                'resolution_minutes' => 10080,
            ],
            default => [
                'first_response_minutes' => 480,
                'resolution_minutes' => 4320,
            ],
        };
    }

    public function hasFirstResponseOverdue(): bool
    {
        return $this->first_response_due_at
            && ! $this->first_responded_at
            && $this->first_response_due_at->isPast();
    }

    public function hasResolutionOverdue(): bool
    {
        return $this->resolution_due_at
            && ! in_array($this->status, ['resolved', 'closed'], true)
            && $this->resolution_due_at->isPast();
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
