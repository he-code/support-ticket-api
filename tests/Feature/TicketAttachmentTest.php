<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_upload_attachment(): void
    {
        Storage::fake();

        $ticket = Ticket::factory()->create();

        $response = $this->postJson("/api/tickets/{$ticket->id}/attachments", [
            'file' => UploadedFile::fake()->image('screenshot.png'),
        ]);

        $response->assertUnauthorized();
    }

    public function test_ticket_owner_can_upload_attachment(): void
    {
        Storage::fake();

        /** @var User $user */
        $user = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson("/api/tickets/{$ticket->id}/attachments", [
            'file' => UploadedFile::fake()->image('screenshot.png'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Attachment uploaded successfully')
            ->assertJsonPath('attachment.original_name', 'screenshot.png');

        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'original_name' => 'screenshot.png',
        ]);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'type' => 'attachment_uploaded',
        ]);
    }

    public function test_user_cannot_upload_attachment_to_other_user_ticket(): void
    {
        Storage::fake();

        /** @var User $owner */
        $owner = User::factory()->create();

        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)->postJson("/api/tickets/{$ticket->id}/attachments", [
            'file' => UploadedFile::fake()->image('screenshot.png'),
        ]);

        $response->assertForbidden();
    }

    public function test_support_agent_can_upload_attachment_to_any_ticket(): void
    {
        Storage::fake();

        /** @var User $agent */
        $agent = User::factory()->supportAgent()->create();

        /** @var User $owner */
        $owner = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($agent)->postJson("/api/tickets/{$ticket->id}/attachments", [
            'file' => UploadedFile::fake()->image('agent-file.png'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('attachment.original_name', 'agent-file.png');

        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'original_name' => 'agent-file.png',
        ]);
    }

    public function test_owner_can_list_attachments_from_own_ticket(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
        ]);

        TicketAttachment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'original_name' => 'document.pdf',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/tickets/{$ticket->id}/attachments");

        $response->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('attachments.0.original_name', 'document.pdf');
    }

    public function test_user_cannot_list_attachments_from_other_user_ticket(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create();

        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->getJson("/api/tickets/{$ticket->id}/attachments");

        $response->assertForbidden();
    }

    public function test_uploader_can_delete_own_attachment(): void
    {
        Storage::fake();

        /** @var User $user */
        $user = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
        ]);

        $filePath = UploadedFile::fake()
            ->image('delete-me.png')
            ->store('ticket-attachments');

        $attachment = TicketAttachment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'original_name' => 'delete-me.png',
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/tickets/{$ticket->id}/attachments/{$attachment->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Attachment deleted successfully');

        $this->assertDatabaseMissing('ticket_attachments', [
            'id' => $attachment->id,
        ]);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'type' => 'attachment_deleted',
        ]);

        Storage::assertMissing($filePath);
    }

    public function test_user_cannot_delete_other_user_attachment(): void
    {
        Storage::fake();

        /** @var User $owner */
        $owner = User::factory()->create();

        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
        ]);

        $attachment = TicketAttachment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->deleteJson("/api/tickets/{$ticket->id}/attachments/{$attachment->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('ticket_attachments', [
            'id' => $attachment->id,
        ]);
    }

    public function test_admin_can_delete_any_attachment(): void
    {
        Storage::fake();

        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        /** @var User $owner */
        $owner = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
        ]);

        $attachment = TicketAttachment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($admin)
            ->deleteJson("/api/tickets/{$ticket->id}/attachments/{$attachment->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('ticket_attachments', [
            'id' => $attachment->id,
        ]);
    }

    public function test_attachment_file_is_required(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/tickets/{$ticket->id}/attachments", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_ticket_owner_can_download_attachment(): void
    {
        Storage::fake();

        /** @var User $user */
        $user = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
        ]);

        $filePath = UploadedFile::fake()
            ->create('document.pdf', 100, 'application/pdf')
            ->store('ticket-attachments');

        $attachment = TicketAttachment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'original_name' => 'document.pdf',
            'file_path' => $filePath,
            'mime_type' => 'application/pdf',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/tickets/{$ticket->id}/attachments/{$attachment->id}/download");

        $response->assertOk();
    }

    public function test_user_cannot_download_attachment_from_other_user_ticket(): void
    {
        Storage::fake();

        /** @var User $owner */
        $owner = User::factory()->create();

        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
        ]);

        $filePath = UploadedFile::fake()
            ->create('document.pdf', 100, 'application/pdf')
            ->store('ticket-attachments');

        $attachment = TicketAttachment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'original_name' => 'document.pdf',
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($otherUser)
            ->getJson("/api/tickets/{$ticket->id}/attachments/{$attachment->id}/download");

        $response->assertForbidden();
    }

    public function test_support_agent_can_download_attachment_from_any_ticket(): void
    {
        Storage::fake();

        /** @var User $agent */
        $agent = User::factory()->supportAgent()->create();

        /** @var User $owner */
        $owner = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $owner->id,
        ]);

        $filePath = UploadedFile::fake()
            ->create('evidence.pdf', 100, 'application/pdf')
            ->store('ticket-attachments');

        $attachment = TicketAttachment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'original_name' => 'evidence.pdf',
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($agent)
            ->getJson("/api/tickets/{$ticket->id}/attachments/{$attachment->id}/download");

        $response->assertOk();
    }

    public function test_cannot_download_attachment_that_does_not_belong_to_ticket(): void
    {
        Storage::fake();

        /** @var User $user */
        $user = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
        ]);

        $otherTicket = Ticket::factory()->create([
            'user_id' => $user->id,
        ]);

        $filePath = UploadedFile::fake()
            ->create('wrong-ticket.pdf', 100, 'application/pdf')
            ->store('ticket-attachments');

        $attachment = TicketAttachment::factory()->create([
            'ticket_id' => $otherTicket->id,
            'user_id' => $user->id,
            'original_name' => 'wrong-ticket.pdf',
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/tickets/{$ticket->id}/attachments/{$attachment->id}/download");

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Attachment not found for this ticket',
            ]);
    }

    public function test_cannot_download_missing_physical_file(): void
    {
        Storage::fake();

        /** @var User $user */
        $user = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
        ]);

        $attachment = TicketAttachment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'original_name' => 'missing.pdf',
            'file_path' => 'ticket-attachments/missing.pdf',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/tickets/{$ticket->id}/attachments/{$attachment->id}/download");

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Attachment file not found',
            ]);
    }
}
