<?php

namespace Tests\Feature;

use App\Models\SupportTeam;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_category_using_frontend_short_route(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/categories', [
            'name' => 'Facturacion',
            'description' => 'Pagos y facturas',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('category.name', 'Facturacion');

        $this->assertDatabaseHas('ticket_categories', [
            'name' => 'Facturacion',
        ]);
    }

    public function test_ticket_supports_urgent_priority_sla_team_and_tags(): void
    {
        $user = User::factory()->create();
        $team = SupportTeam::factory()->create(['name' => 'Billing']);
        $tag = TicketTag::factory()->create(['name' => 'vip']);

        $response = $this->actingAs($user)->postJson('/api/tickets', [
            'title' => 'Pago no aplicado',
            'description' => 'El pago aparece descontado pero no se refleja.',
            'priority' => 'urgent',
            'team_id' => $team->id,
            'tag_ids' => [$tag->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('ticket.priority', 'urgent')
            ->assertJsonPath('ticket.team.id', $team->id)
            ->assertJsonPath('ticket.tags.0.id', $tag->id)
            ->assertJsonPath('ticket.sla.first_response_overdue', false)
            ->assertJsonPath('ticket.sla.resolution_overdue', false);

        $this->assertNotNull($response->json('ticket.sla.first_response_due_at'));
        $this->assertNotNull($response->json('ticket.sla.resolution_due_at'));
    }

    public function test_tickets_can_be_filtered_by_team_tag_and_sla(): void
    {
        $agent = User::factory()->supportAgent()->create();
        $team = SupportTeam::factory()->create();
        $tag = TicketTag::factory()->create();
        $otherTag = TicketTag::factory()->create();

        $matchingTicket = Ticket::factory()->create([
            'team_id' => $team->id,
            'priority' => 'urgent',
            'status' => 'open',
            'first_response_due_at' => now()->subHour(),
            'resolution_due_at' => now()->addHour(),
            'first_responded_at' => null,
            'title' => 'Ticket filtrado',
        ]);
        $matchingTicket->tags()->sync([$tag->id]);

        $otherTicket = Ticket::factory()->create([
            'team_id' => null,
            'priority' => 'low',
            'title' => 'Ticket fuera del filtro',
        ]);
        $otherTicket->tags()->sync([$otherTag->id]);

        $response = $this->actingAs($agent)->getJson(
            "/api/tickets?team_id={$team->id}&tag_id={$tag->id}&priority=urgent&sla=first_response_overdue"
        );

        $response->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('tickets.0.title', 'Ticket filtrado')
            ->assertJsonPath('tickets.0.sla.first_response_overdue', true);
    }

    public function test_staff_can_manage_quick_replies_and_attach_knowledge_base_articles(): void
    {
        $agent = User::factory()->supportAgent()->create();
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);

        $quickReplyResponse = $this->actingAs($agent)->postJson('/api/quick-replies', [
            'title' => 'Solicitar informacion',
            'body' => 'Podrias enviarnos mas detalles para continuar?',
            'is_active' => true,
        ]);

        $quickReplyResponse->assertCreated()
            ->assertJsonPath('quick_reply.title', 'Solicitar informacion');

        $articleResponse = $this->actingAs($agent)->postJson('/api/knowledge-base-articles', [
            'title' => 'Como validar un pago',
            'summary' => 'Pasos para revisar pagos pendientes',
            'content' => 'Revisa referencia, fecha, monto y metodo de pago.',
            'is_published' => true,
        ]);

        $articleResponse->assertCreated()
            ->assertJsonPath('article.slug', 'como-validar-un-pago');

        $articleId = $articleResponse->json('article.id');

        $attachResponse = $this->actingAs($agent)->postJson("/api/tickets/{$ticket->id}/knowledge-base-articles", [
            'article_id' => $articleId,
        ]);

        $attachResponse->assertCreated()
            ->assertJsonPath('article.id', $articleId);

        $this->assertDatabaseHas('knowledge_base_article_ticket', [
            'ticket_id' => $ticket->id,
            'knowledge_base_article_id' => $articleId,
        ]);
    }

    public function test_ticket_owner_can_submit_satisfaction_for_closed_ticket(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
            'status' => 'closed',
        ]);

        $response = $this->actingAs($owner)->postJson("/api/tickets/{$ticket->id}/satisfaction", [
            'rating' => 5,
            'comment' => 'Respuesta clara y rapida.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('survey.rating', 5);

        $this->assertDatabaseHas('ticket_satisfaction_surveys', [
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'rating' => 5,
        ]);
    }

    public function test_automation_rule_can_assign_team_agent_and_priority_on_ticket_creation(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $agent = User::factory()->supportAgent()->create();
        $category = TicketCategory::factory()->create();
        $team = SupportTeam::factory()->create();

        $this->actingAs($admin)->postJson('/api/automation-rules', [
            'name' => 'Facturacion urgente',
            'conditions' => [
                'event' => 'ticket_created',
                'category_id' => $category->id,
            ],
            'actions' => [
                'assigned_to_id' => $agent->id,
                'team_id' => $team->id,
                'priority' => 'urgent',
            ],
            'is_active' => true,
            'priority' => 10,
        ])->assertCreated();

        $response = $this->actingAs($owner)->postJson('/api/tickets', [
            'title' => 'Factura no recibida',
            'description' => 'Necesito una copia de la factura.',
            'priority' => 'medium',
            'category_id' => $category->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('ticket.assigned_to.id', $agent->id)
            ->assertJsonPath('ticket.team.id', $team->id)
            ->assertJsonPath('ticket.priority', 'urgent');

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $response->json('ticket.id'),
            'type' => 'automation_applied',
        ]);
    }
}
