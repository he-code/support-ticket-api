<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_notifications(): void
    {
        $response = $this->getJson('/api/notifications');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_own_notifications(): void
    {
        /** @var User $user */
        $user = User::factory()->supportAgent()->create();

        $ticket = Ticket::factory()->create([
            'assigned_to_id' => $user->id,
        ]);

        $user->notify(new TicketAssignedNotification($ticket));

        $response = $this->actingAs($user)->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.type', 'ticket_assigned')
            ->assertJsonPath('notifications.0.ticket_id', $ticket->id);
    }

    public function test_authenticated_user_can_mark_notification_as_read(): void
    {
        /** @var User $user */
        $user = User::factory()->supportAgent()->create();

        $ticket = Ticket::factory()->create([
            'assigned_to_id' => $user->id,
        ]);

        $user->notify(new TicketAssignedNotification($ticket));

        $notification = $user->notifications()->first();

        $response = $this->actingAs($user)
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertOk()
            ->assertJsonPath('message', 'Notification marked as read');

        $notification->refresh();

        $this->assertNotNull($notification->read_at);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        /** @var User $owner */
        $owner = User::factory()->supportAgent()->create();

        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'assigned_to_id' => $owner->id,
        ]);

        $owner->notify(new TicketAssignedNotification($ticket));

        $notification = $owner->notifications()->first();

        $response = $this->actingAs($otherUser)
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertNotFound();
    }

    public function test_authenticated_user_can_mark_all_notifications_as_read(): void
    {
        /** @var User $user */
        $user = User::factory()->supportAgent()->create();

        $ticketOne = Ticket::factory()->create([
            'assigned_to_id' => $user->id,
        ]);

        $ticketTwo = Ticket::factory()->create([
            'assigned_to_id' => $user->id,
        ]);

        $user->notify(new TicketAssignedNotification($ticketOne));
        $user->notify(new TicketAssignedNotification($ticketTwo));

        $this->assertSame(2, $user->unreadNotifications()->count());

        $response = $this->actingAs($user)
            ->patchJson('/api/notifications/read-all');

        $response->assertOk()
            ->assertJsonPath('message', 'All notifications marked as read');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_assigning_ticket_creates_notification_for_assigned_agent(): void
    {
        /** @var User $agent */
        $agent = User::factory()->supportAgent()->create();

        /** @var User $assignedAgent */
        $assignedAgent = User::factory()->supportAgent()->create();

        /** @var User $owner */
        $owner = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
            'assigned_to_id' => null,
        ]);

        $response = $this->actingAs($agent)
            ->patchJson("/api/tickets/{$ticket->id}/assign", [
                'assigned_to_id' => $assignedAgent->id,
            ]);

        $response->assertOk();

        $this->assertSame(1, $assignedAgent->fresh()->unreadNotifications()->count());

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $assignedAgent->id,
        ]);
    }

    public function test_status_change_creates_notification_for_ticket_owner(): void
    {
        /** @var User $agent */
        $agent = User::factory()->supportAgent()->create();

        /** @var User $owner */
        $owner = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
            'status' => 'open',
        ]);

        $response = $this->actingAs($agent)
            ->patchJson("/api/tickets/{$ticket->id}/status", [
                'status' => 'closed',
            ]);

        $response->assertOk();

        $this->assertSame(1, $owner->fresh()->unreadNotifications()->count());

        $notification = $owner->notifications()->first();

        $this->assertSame('ticket_status_changed', $notification->data['type']);
        $this->assertSame('open', $notification->data['old_status']);
        $this->assertSame('closed', $notification->data['new_status']);
    }

    public function test_agent_comment_creates_notification_for_ticket_owner(): void
    {
        /** @var User $agent */
        $agent = User::factory()->supportAgent()->create();

        /** @var User $owner */
        $owner = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($agent)
            ->postJson("/api/tickets/{$ticket->id}/comments", [
                'body' => 'Respuesta del agente.',
            ]);

        $response->assertCreated();

        $this->assertSame(1, $owner->fresh()->unreadNotifications()->count());

        $notification = $owner->notifications()->first();

        $this->assertSame('ticket_comment_created', $notification->data['type']);
        $this->assertSame($ticket->id, $notification->data['ticket_id']);
    }

    public function test_owner_comment_does_not_create_notification_for_self(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($owner)
            ->postJson("/api/tickets/{$ticket->id}/comments", [
                'body' => 'Comentario del dueño del ticket.',
            ]);

        $response->assertCreated();

        $this->assertSame(0, $owner->fresh()->unreadNotifications()->count());
    }

    public function test_owner_comment_creates_notification_for_assigned_agent(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create();

        /** @var User $agent */
        $agent = User::factory()->supportAgent()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
            'assigned_to_id' => $agent->id,
        ]);

        $response = $this->actingAs($owner)
            ->postJson("/api/tickets/{$ticket->id}/comments", [
                'body' => 'Comentario del dueño para el agente asignado.',
            ]);

        $response->assertCreated();

        $this->assertSame(1, $agent->fresh()->unreadNotifications()->count());
        $this->assertSame(0, $owner->fresh()->unreadNotifications()->count());

        $notification = $agent->notifications()->first();

        $this->assertSame('ticket_comment_created', $notification->data['type']);
        $this->assertSame($ticket->id, $notification->data['ticket_id']);
    }

    public function test_assigning_ticket_to_same_agent_does_not_create_duplicate_notification(): void
    {
        /** @var User $agent */
        $agent = User::factory()->supportAgent()->create();

        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        /** @var User $owner */
        $owner = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
            'assigned_to_id' => $agent->id,
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/tickets/{$ticket->id}/assign", [
                'assigned_to_id' => $agent->id,
            ]);

        $response->assertOk();

        $this->assertSame(0, $agent->fresh()->unreadNotifications()->count());
    }

    public function test_owner_status_change_does_not_create_notification_for_self(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
            'status' => 'open',
        ]);

        $response = $this->actingAs($owner)
            ->patchJson("/api/tickets/{$ticket->id}/status", [
                'status' => 'closed',
            ]);

        $response->assertOk();

        $this->assertSame(0, $owner->fresh()->unreadNotifications()->count());
    }
}
