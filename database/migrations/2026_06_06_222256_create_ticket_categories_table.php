<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->id();

            // Nombre visible de la categoría
            $table->string('name')->unique();

            // Descripción opcional para explicar cuándo usar esta categoría
            $table->string('description')->nullable();

            // Permite desactivar categorías sin eliminarlas
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_categories');
    }
};
