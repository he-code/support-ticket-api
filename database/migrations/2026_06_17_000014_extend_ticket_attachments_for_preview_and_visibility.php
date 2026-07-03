<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->boolean('is_internal')->default(false)->after('size');
            $table->string('preview_path')->nullable()->after('is_internal');
            $table->json('metadata')->nullable()->after('preview_path');

            $table->index('is_internal', 'ticket_attachments_is_internal_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->dropIndex('ticket_attachments_is_internal_idx');
            $table->dropColumn(['is_internal', 'preview_path', 'metadata']);
        });
    }
};
