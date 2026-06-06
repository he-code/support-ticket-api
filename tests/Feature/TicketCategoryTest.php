<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_ticket_categories(): void
    {
        $response = $this->getJson('/api/ticket-categories');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_active_ticket_categories(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        TicketCategory::factory()->create([
            'name' => 'Soporte técnico',
            'is_active' => true,
        ]);

        TicketCategory::factory()->create([
            'name' => 'Categoría inactiva',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->getJson('/api/ticket-categories');

        $response->assertOk()
            ->assertJsonPath('categories.0.name', 'Soporte técnico')
            ->assertJsonMissing([
                'name' => 'Categoría inactiva',
            ]);
    }

    public function test_admin_can_list_all_ticket_categories(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        TicketCategory::factory()->create([
            'name' => 'Activa',
            'is_active' => true,
        ]);

        TicketCategory::factory()->create([
            'name' => 'Inactiva',
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/ticket-categories');

        $response->assertOk()
            ->assertJsonCount(2, 'categories');
    }

    public function test_admin_can_create_ticket_category(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/ticket-categories', [
            'name' => 'Facturación',
            'description' => 'Problemas relacionados con pagos y facturas.',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Ticket category created successfully')
            ->assertJsonPath('category.name', 'Facturación');

        $this->assertDatabaseHas('ticket_categories', [
            'name' => 'Facturación',
            'is_active' => true,
        ]);
    }

    public function test_regular_user_cannot_create_ticket_category(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/ticket-categories', [
            'name' => 'Nueva categoría',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('ticket_categories', [
            'name' => 'Nueva categoría',
        ]);
    }

    public function test_admin_can_update_ticket_category(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $category = TicketCategory::factory()->create([
            'name' => 'Nombre anterior',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patchJson("/api/ticket-categories/{$category->id}", [
            'name' => 'Nombre actualizado',
            'description' => 'Descripción actualizada.',
            'is_active' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Ticket category updated successfully')
            ->assertJsonPath('category.name', 'Nombre actualizado')
            ->assertJsonPath('category.is_active', false);

        $this->assertDatabaseHas('ticket_categories', [
            'id' => $category->id,
            'name' => 'Nombre actualizado',
            'is_active' => false,
        ]);
    }

    public function test_regular_user_cannot_update_ticket_category(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $category = TicketCategory::factory()->create([
            'name' => 'Categoría original',
        ]);

        $response = $this->actingAs($user)->patchJson("/api/ticket-categories/{$category->id}", [
            'name' => 'Categoría editada',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('ticket_categories', [
            'id' => $category->id,
            'name' => 'Categoría original',
        ]);
    }

    public function test_admin_can_delete_ticket_category(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $category = TicketCategory::factory()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/ticket-categories/{$category->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Ticket category deleted successfully');

        $this->assertDatabaseMissing('ticket_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_regular_user_cannot_delete_ticket_category(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $category = TicketCategory::factory()->create();

        $response = $this->actingAs($user)->deleteJson("/api/ticket-categories/{$category->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('ticket_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_ticket_can_be_created_with_category(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $category = TicketCategory::factory()->create([
            'name' => 'Soporte técnico',
        ]);

        $response = $this->actingAs($user)->postJson('/api/tickets', [
            'title' => 'Problema con acceso',
            'description' => 'No puedo iniciar sesión.',
            'priority' => 'high',
            'category_id' => $category->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('ticket.category.id', $category->id)
            ->assertJsonPath('ticket.category.name', 'Soporte técnico');

        $this->assertDatabaseHas('tickets', [
            'title' => 'Problema con acceso',
            'category_id' => $category->id,
        ]);
    }

    public function test_authenticated_user_can_filter_tickets_by_category(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $category = TicketCategory::factory()->create([
            'name' => 'Facturación',
        ]);

        $otherCategory = TicketCategory::factory()->create([
            'name' => 'Soporte técnico',
        ]);

        Ticket::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Ticket de facturación',
        ]);

        Ticket::factory()->create([
            'user_id' => $user->id,
            'category_id' => $otherCategory->id,
            'title' => 'Ticket de soporte',
        ]);

        $response = $this->actingAs($user)->getJson("/api/tickets?category_id={$category->id}");

        $response->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('tickets.0.title', 'Ticket de facturación')
            ->assertJsonPath('tickets.0.category.id', $category->id);
    }

    public function test_category_name_is_required(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/ticket-categories', [
            'description' => 'Sin nombre.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_category_name_must_be_unique(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        TicketCategory::factory()->create([
            'name' => 'Facturación',
        ]);

        $response = $this->actingAs($admin)->postJson('/api/ticket-categories', [
            'name' => 'Facturación',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }
}
