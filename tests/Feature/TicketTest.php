<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_ticket(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tickets', [
            'title' => 'Problema con acceso',
            'description' => 'No puedo ingresar al sistema.',
            'priority' => 'high',
        ]);

        $response->assertStatus(201);

        $response->assertJson([
            'message' => 'Ticket created successfully',
        ]);

        $this->assertDatabaseHas('tickets', [
            'title' => 'Problema con acceso',
            'description' => 'No puedo ingresar al sistema.',
            'priority' => 'high',
            'user_id' => $user->id,
        ]);
    }
    public function test_guest_cannot_create_ticket(): void
    {
    $response = $this->postJson('/api/tickets', [
        'title' => 'Ticket sin login',
        'description' => 'Este ticket no debería crearse.',
        'priority' => 'high',
    ]);

    $response->assertStatus(401);

    $this->assertDatabaseMissing('tickets', [
        'title' => 'Ticket sin login',
    ]);
    }

    public function test_authenticated_user_can_list_only_own_tickets(): void
    {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Ticket::factory()->create([
        'title' => 'Mi ticket',
        'user_id' => $user->id,
    ]);

    Ticket::factory()->create([
        'title' => 'Ticket ajeno',
        'user_id' => $otherUser->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/tickets');

    $response->assertStatus(200);

    $response->assertJsonPath('pagination.total', 1);

    $response->assertJsonPath('tickets.0.title', 'Mi ticket');

    $response->assertJsonMissing([
        'title' => 'Ticket ajeno',
    ]);
    }

    public function test_authenticated_user_can_show_own_ticket(): void
    {
    $user = User::factory()->create();

    $ticket = Ticket::factory()->create([
        'user_id' => $user->id,
        'title' => 'Ticket visible',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/tickets/{$ticket->id}");

    $response->assertStatus(200);

    $response->assertJsonPath('ticket.id', $ticket->id);
    $response->assertJsonPath('ticket.title', 'Ticket visible');
    $response->assertJsonPath('ticket.created_by.id', $user->id);
    }

    public function test_authenticated_user_cannot_show_other_user_ticket(): void
    {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $ticket = Ticket::factory()->create([
        'user_id' => $otherUser->id,
        'title' => 'Ticket ajeno',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/tickets/{$ticket->id}");

    $response->assertStatus(403);

    $response->assertJson([
        'message' => 'Unauthorized',
    ]);
    }

    public function test_authenticated_user_can_update_own_ticket(): void
    {
    $user = User::factory()->create();

    $ticket = Ticket::factory()->create([
        'user_id' => $user->id,
        'title' => 'Título anterior',
        'description' => 'Descripción anterior',
        'priority' => 'low',
        'status' => 'open',
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson("/api/tickets/{$ticket->id}", [
        'title' => 'Título actualizado',
        'description' => 'Descripción actualizada',
        'priority' => 'medium',
        'status' => 'in_progress',
    ]);

    $response->assertStatus(200);

    $response->assertJsonPath('message', 'Ticket updated successfully');
    $response->assertJsonPath('ticket.title', 'Título actualizado');
    $response->assertJsonPath('ticket.description', 'Descripción actualizada');
    $response->assertJsonPath('ticket.priority', 'medium');
    $response->assertJsonPath('ticket.status', 'in_progress');

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'title' => 'Título actualizado',
        'description' => 'Descripción actualizada',
        'priority' => 'medium',
        'status' => 'in_progress',
    ]);
    }

    public function test_authenticated_user_cannot_update_other_user_ticket(): void
    {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $ticket = Ticket::factory()->create([
        'user_id' => $otherUser->id,
        'title' => 'Ticket ajeno',
        'description' => 'Descripción original',
        'priority' => 'low',
        'status' => 'open',
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson("/api/tickets/{$ticket->id}", [
        'title' => 'Intento de modificación',
        'description' => 'Intento de cambiar un ticket ajeno',
        'priority' => 'high',
        'status' => 'resolved',
    ]);

    $response->assertStatus(403);

    $response->assertJson([
        'message' => 'Unauthorized',
    ]);

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'title' => 'Ticket ajeno',
        'description' => 'Descripción original',
        'priority' => 'low',
        'status' => 'open',
    ]);
    }
    public function test_authenticated_user_can_update_ticket_status(): void
    {
    $user = User::factory()->create();

    $ticket = Ticket::factory()->create([
        'user_id' => $user->id,
        'status' => 'open',
    ]);

    Sanctum::actingAs($user);

    $response = $this->patchJson("/api/tickets/{$ticket->id}/status", [
        'status' => 'resolved',
    ]);

    $response->assertStatus(200);

    $response->assertJsonPath('message', 'Ticket status updated successfully');
    $response->assertJsonPath('ticket.status', 'resolved');

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'status' => 'resolved',
    ]);
    }

    public function test_authenticated_user_cannot_update_ticket_status_with_invalid_value(): void
    {
    $user = User::factory()->create();

    $ticket = Ticket::factory()->create([
        'user_id' => $user->id,
        'status' => 'open',
    ]);

    Sanctum::actingAs($user);

    $response = $this->patchJson("/api/tickets/{$ticket->id}/status", [
        'status' => 'invalid_status',
    ]);

    $response->assertStatus(422);

    $response->assertJsonValidationErrors(['status']);

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'status' => 'open',
    ]);
    }

    public function test_authenticated_user_can_delete_own_ticket(): void
    {
    $user = User::factory()->create();

    $ticket = Ticket::factory()->create([
        'user_id' => $user->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->deleteJson("/api/tickets/{$ticket->id}");

    $response->assertStatus(200);

    $response->assertJson([
        'message' => 'Ticket deleted successfully',
    ]);

    $this->assertDatabaseMissing('tickets', [
        'id' => $ticket->id,
    ]);
    }

    public function test_authenticated_user_cannot_delete_other_user_ticket(): void
    {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $ticket = Ticket::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->deleteJson("/api/tickets/{$ticket->id}");

    $response->assertStatus(403);

    $response->assertJson([
        'message' => 'Unauthorized',
    ]);

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
    ]);
    }
}