<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_users(): void
    {
        $response = $this->getJson('/api/users');

        $response->assertUnauthorized();
    }

    public function test_admin_can_list_users(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        User::factory()->create([
            'name' => 'Usuario Normal',
            'role' => 'user',
        ]);

        User::factory()->supportAgent()->create([
            'name' => 'Agente Soporte',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/users');

        $response->assertOk()
            ->assertJsonPath('pagination.total', 3)
            ->assertJsonStructure([
                'users' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'created_at',
                    ],
                ],
                'pagination',
            ]);
    }

    public function test_support_agent_cannot_list_users(): void
    {
        /** @var User $agent */
        $agent = User::factory()->supportAgent()->create();

        $response = $this->actingAs($agent)->getJson('/api/users');

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Unauthorized',
            ]);
    }

    public function test_regular_user_cannot_list_users(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/users');

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Unauthorized',
            ]);
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        User::factory()->create([
            'role' => 'user',
        ]);

        User::factory()->supportAgent()->create();

        $response = $this->actingAs($admin)->getJson('/api/users?role=support_agent');

        $response->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('users.0.role', 'support_agent');
    }

    public function test_admin_can_search_users_by_name(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        User::factory()->create([
            'name' => 'Carlos Pérez',
        ]);

        User::factory()->create([
            'name' => 'María López',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/users?search=Carlos');

        $response->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('users.0.name', 'Carlos Pérez');
    }

    public function test_admin_can_update_user_role(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        /** @var User $user */
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/users/{$user->id}/role", [
                'role' => 'support_agent',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'User role updated successfully')
            ->assertJsonPath('user.role', 'support_agent');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'support_agent',
        ]);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->patchJson("/api/users/{$admin->id}/role", [
                'role' => 'user',
            ]);

        $response->assertForbidden()
            ->assertJson([
                'message' => 'You cannot change your own role',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'admin',
        ]);
    }

    public function test_support_agent_cannot_update_user_role(): void
    {
        /** @var User $agent */
        $agent = User::factory()->supportAgent()->create();

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($agent)
            ->patchJson("/api/users/{$user->id}/role", [
                'role' => 'admin',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'user',
        ]);
    }

    public function test_user_role_is_required(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($admin)
            ->patchJson("/api/users/{$user->id}/role", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    public function test_user_role_must_be_valid(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($admin)
            ->patchJson("/api/users/{$user->id}/role", [
                'role' => 'super_admin',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }
}
