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

    public function test_authenticated_user_can_filter_tickets_by_status(): void
    {
    $user = User::factory()->create();

    Ticket::factory()->create([
        'user_id' => $user->id,
        'title' => 'Ticket abierto',
        'status' => 'open',
    ]);

    Ticket::factory()->create([
        'user_id' => $user->id,
        'title' => 'Ticket resuelto',
        'status' => 'resolved',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/tickets?status=resolved');

    $response->assertStatus(200);

    $response->assertJsonPath('pagination.total', 1);
    $response->assertJsonPath('tickets.0.title', 'Ticket resuelto');
    $response->assertJsonPath('tickets.0.status', 'resolved');

    $response->assertJsonMissing([
        'title' => 'Ticket abierto',
    ]);
    }

    public function test_authenticated_user_can_filter_tickets_by_priority(): void
    {
    $user = User::factory()->create();

    Ticket::factory()->create([
        'user_id' => $user->id,
        'title' => 'Ticket baja prioridad',
        'priority' => 'low',
    ]);

    Ticket::factory()->create([
        'user_id' => $user->id,
        'title' => 'Ticket alta prioridad',
        'priority' => 'high',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/tickets?priority=high');

    $response->assertStatus(200);

    $response->assertJsonPath('pagination.total', 1);
    $response->assertJsonPath('tickets.0.title', 'Ticket alta prioridad');
    $response->assertJsonPath('tickets.0.priority', 'high');

    $response->assertJsonMissing([
        'title' => 'Ticket baja prioridad',
    ]);
    }

    public function test_authenticated_user_can_search_tickets_by_title_or_description(): void
    {
    $user = User::factory()->create();

    Ticket::factory()->create([
        'user_id' => $user->id,
        'title' => 'Error al iniciar sesión',
        'description' => 'No puedo entrar con mi contraseña.',
    ]);

    Ticket::factory()->create([
        'user_id' => $user->id,
        'title' => 'Problema con impresora',
        'description' => 'La impresora no responde.',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/tickets?search=contraseña');

    $response->assertStatus(200);

    $response->assertJsonPath('pagination.total', 1);
    $response->assertJsonPath('tickets.0.title', 'Error al iniciar sesión');

    $response->assertJsonMissing([
        'title' => 'Problema con impresora',
    ]);
    }
    public function test_authenticated_user_can_sort_tickets_by_title(): void
    {
    $user = User::factory()->create();

    Ticket::factory()->create([
        'user_id' => $user->id,
        'title' => 'Zebra ticket',
    ]);

    Ticket::factory()->create([
        'user_id' => $user->id,
        'title' => 'Alpha ticket',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/tickets?sort_by=title&sort_direction=asc');

    $response->assertStatus(200);

    $response->assertJsonPath('pagination.total', 2);
    $response->assertJsonPath('tickets.0.title', 'Alpha ticket');
    $response->assertJsonPath('tickets.1.title', 'Zebra ticket');
    }

    public function test_authenticated_user_gets_default_sort_when_sort_by_is_invalid(): void
    {
    $user = User::factory()->create();

    $oldTicket = Ticket::factory()->create([
        'user_id' => $user->id,
        'title' => 'Ticket antiguo',
        'created_at' => now()->subDay(),
    ]);

    $newTicket = Ticket::factory()->create([
        'user_id' => $user->id,
        'title' => 'Ticket nuevo',
        'created_at' => now(),
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/tickets?sort_by=invalid_column&sort_direction=asc');

    $response->assertStatus(200);

    $response->assertJsonPath('pagination.total', 2);
    $response->assertJsonPath('tickets.0.id', $newTicket->id);
    $response->assertJsonPath('tickets.1.id', $oldTicket->id);
    }

    public function test_authenticated_user_gets_default_sort_direction_when_sort_direction_is_invalid(): void
    {
    $user = User::factory()->create();

    $oldTicket = Ticket::factory()->create([
        'user_id' => $user->id,
        'title' => 'Ticket antiguo',
        'created_at' => now()->subDay(),
    ]);

    $newTicket = Ticket::factory()->create([
        'user_id' => $user->id,
        'title' => 'Ticket nuevo',
        'created_at' => now(),
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/tickets?sort_by=created_at&sort_direction=invalid_direction');

    $response->assertStatus(200);

    $response->assertJsonPath('pagination.total', 2);
    $response->assertJsonPath('tickets.0.id', $newTicket->id);
    $response->assertJsonPath('tickets.1.id', $oldTicket->id);
    }

    public function test_authenticated_user_cannot_filter_tickets_by_invalid_status(): void
    {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/tickets?status=invalid_status');

    $response->assertStatus(422);

    $response->assertJsonValidationErrors(['status']);
    }

    public function test_authenticated_user_cannot_filter_tickets_by_invalid_priority(): void
    {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/tickets?priority=urgent');

    $response->assertStatus(422);

    $response->assertJsonValidationErrors(['priority']);
    }
}