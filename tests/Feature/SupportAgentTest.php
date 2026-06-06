<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_support_agents(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        /** @var User $agent */
        $agent = User::factory()->supportAgent()->create([
            'name' => 'Carlos Soporte',
        ]);

        User::factory()->create([
            'name' => 'Usuario Normal',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/support-agents');

        $response->assertOk()
            ->assertJsonPath('support_agents.0.name', 'Carlos Soporte');

        $response->assertJsonMissing([
            'name' => 'Usuario Normal',
        ]);
    }

    public function test_support_agent_can_list_support_agents(): void
    {
        /** @var User $agent */
        $agent = User::factory()->supportAgent()->create();

        User::factory()->supportAgent()->create([
            'name' => 'Agente Dos',
        ]);

        $response = $this->actingAs($agent)->getJson('/api/support-agents');

        $response->assertOk()
            ->assertJsonStructure([
                'support_agents' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                    ],
                ],
            ]);
    }

    public function test_regular_user_cannot_list_support_agents(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/support-agents');

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Unauthorized',
            ]);
    }

    public function test_guest_cannot_list_support_agents(): void
    {
        $response = $this->getJson('/api/support-agents');

        $response->assertUnauthorized();
    }
}
