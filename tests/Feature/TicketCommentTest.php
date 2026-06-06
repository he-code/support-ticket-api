<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class TicketCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_comment_on_own_ticket(): void
    {
        $user = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
        ]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user)->postJson("/api/tickets/{$ticket->id}/comments", [
            'body' => 'This is a test comment.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Comment created successfully')
            ->assertJsonPath('comment.body', 'This is a test comment.');

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => 'This is a test comment.',
        ]);
    }

    public function test_guest_cannot_create_comment(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->postJson("/api/tickets/{$ticket->id}/comments", [
            'body' => 'Unauthorized comment.',
        ]);

        $response->assertUnauthorized();
    }

    public function test_user_cannot_comment_on_other_user_ticket(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
        ]);

        /** @var \App\Models\User $otherUser */
        $response = $this->actingAs($otherUser)->postJson("/api/tickets/{$ticket->id}/comments", [
            'body' => 'Trying to comment.',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('ticket_comments', [
            'ticket_id' => $ticket->id,
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_authenticated_user_can_list_comments_from_own_ticket(): void
    {
        $user = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
        ]);

        TicketComment::factory()->count(2)->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        /** @var \App\Models\User $user */
        $response = $this->actingAs($user)->getJson("/api/tickets/{$ticket->id}/comments");

        $response->assertOk()
            ->assertJsonStructure([
                'comments',
                'pagination',
            ]);
    }

    public function test_user_cannot_list_comments_from_other_user_ticket(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
        ]);

        /** @var \App\Models\User $otherUser */
        $response = $this->actingAs($otherUser)->getJson("/api/tickets/{$ticket->id}/comments");

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_delete_comment_from_own_ticket(): void
    {
        $user = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
        ]);

        $comment = TicketComment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        /** @var \App\Models\User $user */
        $response = $this->actingAs($user)
            ->deleteJson("/api/tickets/{$ticket->id}/comments/{$comment->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Comment deleted successfully');

        $this->assertDatabaseMissing('ticket_comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_comment_body_is_required(): void
    {
        $user = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
        ]);

        /** @var \App\Models\User $user */
        $response = $this->actingAs($user)->postJson("/api/tickets/{$ticket->id}/comments", [
            'body' => '',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['body']);
    }

    public function test_support_agent_can_comment_on_any_ticket(): void
    {
    $agent = User::factory()->create([
        'role' => 'support_agent',
    ]);

    $owner = User::factory()->create();

    $ticket = Ticket::factory()->create([
        'user_id' => $owner->id,
    ]);

    /** @var \App\Models\User $agent */
    $response = $this->actingAs($agent)->postJson("/api/tickets/{$ticket->id}/comments", [
        'body' => 'Respuesta del agente de soporte.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('comment.body', 'Respuesta del agente de soporte.');

    $this->assertDatabaseHas('ticket_comments', [
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'body' => 'Respuesta del agente de soporte.',
    ]);
    }
}