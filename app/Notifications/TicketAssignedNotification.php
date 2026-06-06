<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Ticket $ticket
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'ticket_assigned',
            'title' => 'Ticket assigned to you',
            'message' => "You have been assigned to ticket #{$this->ticket->id}.",
            'ticket_id' => $this->ticket->id,
            'ticket_title' => $this->ticket->title,
        ];
    }
}