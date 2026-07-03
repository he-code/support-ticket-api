<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;

class MentionService
{
    public function createMentions(
        Ticket $ticket,
        array $userIds,
        User $mentionedBy,
        string $sourceType,
        int $sourceId
    ): void {
        $userIds = collect($userIds)
            ->filter()
            ->unique()
            ->values();

        foreach ($userIds as $userId) {
            $mention = $ticket->mentions()->create([
                'mentioned_user_id' => $userId,
                'mentioned_by_id' => $mentionedBy->id,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);

            $ticket->recordActivity(
                type: 'user_mentioned',
                user: $mentionedBy,
                description: 'User mentioned in ticket',
                metadata: [
                    'mention_id' => $mention->id,
                    'mentioned_user_id' => $userId,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                ]
            );
        }
    }
}
