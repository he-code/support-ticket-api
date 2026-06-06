<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketAttachmentRequest;
use App\Http\Resources\TicketAttachmentResource;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketAttachmentController extends Controller
{
    // Listar adjuntos de un ticket
    public function index(Request $request, Ticket $ticket)
    {
        // Solo puede ver adjuntos quien puede ver el ticket
        if ($request->user()->cannot('view', $ticket)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $attachments = $ticket->attachments()
            ->with('user')
            ->latest()
            ->paginate(10);

        return response()->json([
            'attachments' => TicketAttachmentResource::collection($attachments),
            'pagination' => [
                'total' => $attachments->total(),
                'per_page' => $attachments->perPage(),
                'current_page' => $attachments->currentPage(),
                'last_page' => $attachments->lastPage(),
                'from' => $attachments->firstItem(),
                'to' => $attachments->lastItem(),
            ],
        ]);
    }

    // Subir adjunto a un ticket
    public function store(StoreTicketAttachmentRequest $request, Ticket $ticket)
    {
        // Solo puede subir adjuntos quien puede ver el ticket
        if ($request->user()->cannot('view', $ticket)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $file = $request->file('file');

        // Guardamos el archivo en storage/app/ticket-attachments
        $path = $file->store('ticket-attachments');

        $attachment = $ticket->attachments()->create([
            'user_id' => $request->user()->id,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        // Registramos actividad en el historial del ticket
        $ticket->recordActivity(
            type: 'attachment_uploaded',
            user: $request->user(),
            description: 'Attachment uploaded to ticket',
            metadata: [
                'attachment_id' => $attachment->id,
                'original_name' => $attachment->original_name,
            ]
        );

        $attachment->load('user');

        return response()->json([
            'message' => 'Attachment uploaded successfully',
            'attachment' => new TicketAttachmentResource($attachment),
        ], 201);
    }

    // Eliminar adjunto de un ticket
    public function destroy(Request $request, Ticket $ticket, TicketAttachment $attachment)
    {
        // Validamos que el adjunto pertenezca al ticket indicado
        if ($attachment->ticket_id !== $ticket->id) {
            return response()->json([
                'message' => 'Attachment not found for this ticket',
            ], 404);
        }

        // Puede eliminarlo el admin o quien subió el archivo
        if (
            ! $request->user()->isAdmin()
            && $attachment->user_id !== $request->user()->id
        ) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $attachmentId = $attachment->id;
        $originalName = $attachment->original_name;
        $filePath = $attachment->file_path;

        // Eliminamos el archivo físico del storage
        Storage::delete($filePath);

        // Eliminamos el registro de base de datos
        $attachment->delete();

        // Registramos actividad en el historial del ticket
        $ticket->recordActivity(
            type: 'attachment_deleted',
            user: $request->user(),
            description: 'Attachment deleted from ticket',
            metadata: [
                'attachment_id' => $attachmentId,
                'original_name' => $originalName,
            ]
        );

        return response()->json([
            'message' => 'Attachment deleted successfully',
        ]);
    }
}
