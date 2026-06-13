<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Índices simples para filtros frecuentes
            $table->index('user_id', 'tickets_user_id_idx');
            $table->index('assigned_to_id', 'tickets_assigned_to_id_idx');
            $table->index('category_id', 'tickets_category_id_idx');
            $table->index('status', 'tickets_status_idx');
            $table->index('priority', 'tickets_priority_idx');
            $table->index('created_at', 'tickets_created_at_idx');

            // Índices compuestos para búsquedas combinadas
            $table->index(['user_id', 'status'], 'tickets_user_status_idx');
            $table->index(['assigned_to_id', 'status'], 'tickets_assigned_status_idx');
            $table->index(['category_id', 'status'], 'tickets_category_status_idx');
            $table->index(['status', 'priority'], 'tickets_status_priority_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_user_id_idx');
            $table->dropIndex('tickets_assigned_to_id_idx');
            $table->dropIndex('tickets_category_id_idx');
            $table->dropIndex('tickets_status_idx');
            $table->dropIndex('tickets_priority_idx');
            $table->dropIndex('tickets_created_at_idx');

            $table->dropIndex('tickets_user_status_idx');
            $table->dropIndex('tickets_assigned_status_idx');
            $table->dropIndex('tickets_category_status_idx');
            $table->dropIndex('tickets_status_priority_idx');
        });
    }
};
