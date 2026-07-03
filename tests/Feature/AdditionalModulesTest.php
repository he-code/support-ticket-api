<?php

namespace Tests\Feature;

use App\Models\SupportTeam;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdditionalModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_channel_and_ticket_can_use_it(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();

        $channelResponse = $this->actingAs($admin)->postJson('/api/ticket-channels', [
            'name' => 'WhatsApp',
            'key' => 'whatsapp',
            'is_active' => true,
        ]);

        $channelResponse->assertCreated()
            ->assertJsonPath('channel.key', 'whatsapp');

        $channelId = $channelResponse->json('channel.id');

        $ticketResponse = $this->actingAs($owner)->postJson('/api/tickets', [
            'title' => 'Mensaje por WhatsApp',
            'description' => 'El cliente escribio desde WhatsApp.',
            'priority' => 'medium',
            'channel_id' => $channelId,
        ]);

        $ticketResponse->assertCreated()
            ->assertJsonPath('ticket.channel.id', $channelId);
    }

    public function test_staff_can_create_internal_note_with_mentions(): void
    {
        $agent = User::factory()->supportAgent()->create();
        $mentioned = User::factory()->supportAgent()->create();
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($agent)->postJson("/api/tickets/{$ticket->id}/internal-notes", [
            'body' => 'Revisar con segundo nivel.',
            'mention_user_ids' => [$mentioned->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('internal_note.body', 'Revisar con segundo nivel.');

        $this->assertDatabaseHas('ticket_mentions', [
            'ticket_id' => $ticket->id,
            'mentioned_user_id' => $mentioned->id,
        ]);

        $this->actingAs($owner)
            ->getJson("/api/tickets/{$ticket->id}/internal-notes")
            ->assertForbidden();
    }

    public function test_staff_can_escalate_ticket(): void
    {
        $agent = User::factory()->supportAgent()->create();
        $senior = User::factory()->supportAgent()->create();
        $team = SupportTeam::factory()->create();
        $ticket = Ticket::factory()->create(['priority' => 'medium']);

        $response = $this->actingAs($agent)->postJson("/api/tickets/{$ticket->id}/escalations", [
            'reason' => 'Impacto alto',
            'to_priority' => 'urgent',
            'escalated_to_id' => $senior->id,
            'team_id' => $team->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('escalation.to_priority', 'urgent')
            ->assertJsonPath('escalation.escalated_to.id', $senior->id);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'priority' => 'urgent',
            'assigned_to_id' => $senior->id,
            'team_id' => $team->id,
        ]);
    }

    public function test_custom_fields_are_saved_on_ticket_creation(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $category = TicketCategory::factory()->create();

        $fieldResponse = $this->actingAs($admin)->postJson('/api/custom-fields', [
            'category_id' => $category->id,
            'name' => 'Numero de factura',
            'key' => 'invoice_number',
            'type' => 'text',
            'is_required' => true,
        ]);

        $fieldResponse->assertCreated();

        $ticketResponse = $this->actingAs($owner)->postJson('/api/tickets', [
            'title' => 'No llega factura',
            'description' => 'Necesito copia de la factura.',
            'priority' => 'high',
            'category_id' => $category->id,
            'custom_fields' => [
                'invoice_number' => 'FAC-100',
            ],
        ]);

        $ticketResponse->assertCreated()
            ->assertJsonPath('ticket.custom_fields.0.key', 'invoice_number')
            ->assertJsonPath('ticket.custom_fields.0.value', 'FAC-100');
    }

    public function test_webhook_delivery_is_recorded_when_ticket_is_created(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();

        $this->actingAs($admin)->postJson('/api/integrations/webhooks', [
            'name' => 'CRM',
            'url' => 'https://example.com/hooks/support',
            'events' => ['ticket.created'],
            'is_active' => true,
        ])->assertCreated();

        $this->actingAs($owner)->postJson('/api/tickets', [
            'title' => 'Evento externo',
            'description' => 'Debe registrar una entrega pendiente.',
            'priority' => 'low',
        ])->assertCreated();

        $this->assertDatabaseHas('webhook_deliveries', [
            'event' => 'ticket.created',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_import_users_from_csv(): void
    {
        $admin = User::factory()->admin()->create();
        $csv = "name,email,role,password\nAna Importada,ana.importada@example.com,support_agent,password123\n";
        $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

        $response = $this->actingAs($admin)->postJson('/api/users/import', [
            'file' => $file,
        ]);

        $response->assertCreated()
            ->assertJsonPath('import.created_count', 1)
            ->assertJsonPath('import.skipped_count', 0);

        $this->assertDatabaseHas('users', [
            'email' => 'ana.importada@example.com',
            'role' => 'support_agent',
        ]);
    }

    public function test_non_admin_users_cannot_import_users_from_csv(): void
    {
        $actors = [
            User::factory()->create(),
            User::factory()->supportAgent()->create(),
        ];

        foreach ($actors as $actor) {
            $csv = "name,email,role,password\nNo Permitido {$actor->id},blocked{$actor->id}@example.com,user,password123\n";
            $file = UploadedFile::fake()->createWithContent("users-{$actor->id}.csv", $csv);

            $this->actingAs($actor)->postJson('/api/users/import', [
                'file' => $file,
            ])->assertForbidden()
                ->assertJsonPath('message', 'Unauthorized');

            $this->assertDatabaseMissing('users', [
                'email' => "blocked{$actor->id}@example.com",
            ]);
        }
    }

    public function test_non_admin_users_cannot_view_user_import_history(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/api/users/imports')
            ->assertForbidden()
            ->assertJsonPath('message', 'Unauthorized');

        $this->actingAs(User::factory()->supportAgent()->create())
            ->getJson('/api/users/imports')
            ->assertForbidden()
            ->assertJsonPath('message', 'Unauthorized');
    }

    public function test_internal_attachments_are_hidden_from_ticket_owner_and_previewable_for_staff(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $agent = User::factory()->supportAgent()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);

        $upload = $this->actingAs($agent)->postJson("/api/tickets/{$ticket->id}/attachments", [
            'file' => UploadedFile::fake()->createWithContent('evidence.txt', 'internal evidence'),
            'is_internal' => true,
        ]);

        $upload->assertCreated()
            ->assertJsonPath('attachment.is_internal', true)
            ->assertJsonPath('attachment.is_previewable', true);

        $this->actingAs($owner)
            ->getJson("/api/tickets/{$ticket->id}/attachments")
            ->assertOk()
            ->assertJsonPath('pagination.total', 0);

        $this->actingAs($agent)
            ->get("/api/tickets/{$ticket->id}/attachments/{$upload->json('attachment.id')}/preview")
            ->assertOk();
    }

    public function test_staff_can_export_ticket_report_as_csv(): void
    {
        $agent = User::factory()->supportAgent()->create();
        Ticket::factory()->create(['title' => 'Ticket exportable']);

        $response = $this->actingAs($agent)->get('/api/reports/tickets/export');

        $response->assertOk();
        $this->assertStringContainsString('tickets-report.csv', $response->headers->get('content-disposition'));
    }
}
