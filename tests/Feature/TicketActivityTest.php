<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_ticket_activities(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->getJson("/api/tickets/{$ticket->id}/activities");

        $response->assertUnauthorized();
    }

    public function test_owner_can_list_activities_from_own_ticket(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
        ]);

        TicketActivity::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'type' => 'ticket_created',
            'description' => 'Ticket created',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/tickets/{$ticket->id}/activities");

        $response->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('activities.0.type', 'ticket_created');
    }

    public function test_user_cannot_list_activities_from_other_user_ticket(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create();

        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->getJson("/api/tickets/{$ticket->id}/activities");

        $response->assertForbidden();
    }

    public function test_status_change_creates_activity(): void
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

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'type' => 'status_changed',
            'old_value' => 'open',
            'new_value' => 'closed',
        ]);
    }

    public function test_ticket_assignment_creates_activity(): void
    {
        /** @var User $agent */
        $agent = User::factory()->supportAgent()->create();

        /** @var User $assignedAgent */
        $assignedAgent = User::factory()->supportAgent()->create();

        /** @var User $owner */
        $owner = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($agent)
            ->patchJson("/api/tickets/{$ticket->id}/assign", [
                'assigned_to_id' => $assignedAgent->id,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'type' => 'ticket_assigned',
            'new_value' => (string) $assignedAgent->id,
        ]);
    }

    public function test_comment_creation_creates_activity(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/tickets/{$ticket->id}/comments", [
                'body' => 'Este es un comentario de prueba.',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'type' => 'comment_created',
        ]);
    }
}
