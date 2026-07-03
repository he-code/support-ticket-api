<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->expandWorkflowEnums();

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('team_id')
                ->nullable()
                ->after('category_id')
                ->constrained('support_teams')
                ->nullOnDelete();

            $table->timestamp('first_response_due_at')->nullable()->after('assigned_to_id');
            $table->timestamp('resolution_due_at')->nullable()->after('first_response_due_at');
            $table->timestamp('first_responded_at')->nullable()->after('resolution_due_at');
            $table->timestamp('resolved_at')->nullable()->after('first_responded_at');
            $table->timestamp('closed_at')->nullable()->after('resolved_at');

            $table->index('team_id', 'tickets_team_id_idx');
            $table->index('first_response_due_at', 'tickets_first_response_due_at_idx');
            $table->index('resolution_due_at', 'tickets_resolution_due_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_team_id_idx');
            $table->dropIndex('tickets_first_response_due_at_idx');
            $table->dropIndex('tickets_resolution_due_at_idx');

            $table->dropConstrainedForeignId('team_id');
            $table->dropColumn([
                'first_response_due_at',
                'resolution_due_at',
                'first_responded_at',
                'resolved_at',
                'closed_at',
            ]);
        });

        $this->shrinkWorkflowEnums();
    }

    private function expandWorkflowEnums(): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE tickets MODIFY status ENUM('open','in_progress','waiting_customer','waiting_internal','resolved','closed','reopened') NOT NULL DEFAULT 'open'");
        DB::statement("ALTER TABLE tickets MODIFY priority ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium'");
    }

    private function shrinkWorkflowEnums(): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("UPDATE tickets SET status = 'open' WHERE status IN ('waiting_customer','waiting_internal','reopened')");
        DB::statement("UPDATE tickets SET priority = 'high' WHERE priority = 'urgent'");
        DB::statement("ALTER TABLE tickets MODIFY status ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open'");
        DB::statement("ALTER TABLE tickets MODIFY priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium'");
    }
};
