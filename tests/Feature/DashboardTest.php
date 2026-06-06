<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_dashboard_stats(): void
    {
        $response = $this->getJson('/api/dashboard/stats');

        $response->assertUnauthorized();
    }

    public function test_regular_user_can_only_see_own_ticket_stats(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        Ticket::factory()->create([
            'user_id' => $user->id,
            'status' => 'open',
            'priority' => 'high',
            'assigned_to_id' => null,
        ]);

        Ticket::factory()->create([
            'user_id' => $user->id,
            'status' => 'closed',
            'priority' => 'medium',
            'assigned_to_id' => null,
        ]);

        Ticket::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'open',
            'priority' => 'low',
            'assigned_to_id' => null,
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/stats');

        $response->assertOk()
            ->assertJsonPath('total_tickets', 2)
            ->assertJsonPath('by_status.open', 1)
            ->assertJsonPath('by_status.closed', 1)
            ->assertJsonPath('by_priority.high', 1)
            ->assertJsonPath('by_priority.medium', 1)
            ->assertJsonPath('unassigned_tickets', 2);
    }

    public function test_support_agent_can_see_global_ticket_stats(): void
    {
        /** @var User $agent */
        $agent = User::factory()->supportAgent()->create();

        /** @var User $owner */
        $owner = User::factory()->create();

        Ticket::factory()->create([
            'user_id' => $owner->id,
            'assigned_to_id' => $agent->id,
            'status' => 'open',
            'priority' => 'high',
        ]);

        Ticket::factory()->create([
            'user_id' => $owner->id,
            'assigned_to_id' => null,
            'status' => 'in_progress',
            'priority' => 'medium',
        ]);

        Ticket::factory()->create([
            'user_id' => $owner->id,
            'assigned_to_id' => null,
            'status' => 'resolved',
            'priority' => 'low',
        ]);

        $response = $this->actingAs($agent)->getJson('/api/dashboard/stats');

        $response->assertOk()
            ->assertJsonPath('total_tickets', 3)
            ->assertJsonPath('by_status.open', 1)
            ->assertJsonPath('by_status.in_progress', 1)
            ->assertJsonPath('by_status.resolved', 1)
            ->assertJsonPath('by_priority.high', 1)
            ->assertJsonPath('by_priority.medium', 1)
            ->assertJsonPath('by_priority.low', 1)
            ->assertJsonPath('unassigned_tickets', 2)
            ->assertJsonPath('assigned_to_me', 1);
    }

    public function test_admin_can_see_global_ticket_stats(): void
    {
        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        /** @var User $userOne */
        $userOne = User::factory()->create();

        /** @var User $userTwo */
        $userTwo = User::factory()->create();

        Ticket::factory()->create([
            'user_id' => $userOne->id,
            'status' => 'open',
            'priority' => 'high',
        ]);

        Ticket::factory()->create([
            'user_id' => $userTwo->id,
            'status' => 'closed',
            'priority' => 'low',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/dashboard/stats');

        $response->assertOk()
            ->assertJsonPath('total_tickets', 2)
            ->assertJsonPath('by_status.open', 1)
            ->assertJsonPath('by_status.closed', 1)
            ->assertJsonPath('by_priority.high', 1)
            ->assertJsonPath('by_priority.low', 1);
    }
}