<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();

            // Ticket al que pertenece el adjunto
            $table->foreignId('ticket_id')
                ->constrained()
                ->cascadeOnDelete();

            // Usuario que subió el archivo
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Nombre original del archivo subido por el usuario
            $table->string('original_name');

            // Ruta interna donde Laravel guardó el archivo
            $table->string('file_path');

            // Tipo MIME del archivo: image/png, application/pdf, etc.
            $table->string('mime_type')->nullable();

            // Tamaño del archivo en bytes
            $table->unsignedBigInteger('size')->default(0);

            $table->timestamps();

            // Índice para listar adjuntos de un ticket más rápido
            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_attachments');
    }
};
