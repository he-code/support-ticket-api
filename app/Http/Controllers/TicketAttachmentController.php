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

        // Listamos los adjuntos del ticket con el usuario que los subió
        $attachments = $ticket->attachments()
            ->with('user')
            ->when(! $request->user()->isStaff(), fn ($query) => $query->where('is_internal', false))
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

        if ($request->boolean('is_internal') && ! $request->user()->isStaff()) {
            return response()->json([
                'message' => 'Only staff can upload internal attachments',
            ], 403);
        }

        // Obtenemos el archivo enviado en el campo "file"
        $file = $request->file('file');

        // Guardamos el archivo dentro de storage/app/ticket-attachments
        $path = $file->store('ticket-attachments');

        // Creamos el registro del adjunto en base de datos
        $attachment = $ticket->attachments()->create([
            'user_id' => $request->user()->id,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'is_internal' => $request->boolean('is_internal'),
            'preview_path' => $path,
            'metadata' => [
                'extension' => $file->getClientOriginalExtension(),
                'previewable' => str_starts_with((string) $file->getMimeType(), 'image/')
                    || in_array($file->getMimeType(), ['application/pdf', 'text/plain'], true),
            ],
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
        // Validamos que el adjunto realmente pertenezca al ticket de la URL
        if ($attachment->ticket_id !== $ticket->id) {
            return response()->json([
                'message' => 'Attachment not found for this ticket',
            ], 404);
        }

        // Puede eliminar el adjunto el admin o el usuario que lo subió
        if (
            ! $request->user()->isAdmin()
            && $attachment->user_id !== $request->user()->id
        ) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Guardamos datos antes de eliminar para registrarlos en la actividad
        $attachmentId = $attachment->id;
        $originalName = $attachment->original_name;
        $filePath = $attachment->file_path;

        // Eliminamos el archivo físico del storage
        Storage::delete($filePath);

        // Eliminamos el registro de la base de datos
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

    // Descargar adjunto de forma segura
    public function download(Request $request, Ticket $ticket, TicketAttachment $attachment)
    {
        // Solo puede descargar adjuntos quien puede ver el ticket
        if ($request->user()->cannot('view', $ticket)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Validamos que el adjunto realmente pertenezca al ticket de la URL
        if ($attachment->ticket_id !== $ticket->id) {
            return response()->json([
                'message' => 'Attachment not found for this ticket',
            ], 404);
        }

        // Validamos que el archivo físico exista en storage
        if (! Storage::exists($attachment->file_path)) {
            return response()->json([
                'message' => 'Attachment file not found',
            ], 404);
        }

        if ($attachment->is_internal && ! $request->user()->isStaff()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Descargamos el archivo usando su nombre original
        return Storage::download(
            $attachment->file_path,
            $attachment->original_name
        );
    }

    public function preview(Request $request, Ticket $ticket, TicketAttachment $attachment)
    {
        if ($request->user()->cannot('view', $ticket)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($attachment->ticket_id !== $ticket->id) {
            return response()->json([
                'message' => 'Attachment not found for this ticket',
            ], 404);
        }

        if ($attachment->is_internal && ! $request->user()->isStaff()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if (! $attachment->isPreviewable()) {
            return response()->json([
                'message' => 'Attachment is not previewable',
            ], 422);
        }

        $path = $attachment->preview_path ?: $attachment->file_path;

        if (! Storage::exists($path)) {
            return response()->json([
                'message' => 'Attachment preview not found',
            ], 404);
        }

        return Storage::response(
            $path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream']
        );
    }
}
