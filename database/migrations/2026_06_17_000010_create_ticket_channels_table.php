<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('key')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('channel_id')
                ->nullable()
                ->after('category_id')
                ->constrained('ticket_channels')
                ->nullOnDelete();

            $table->index('channel_id', 'tickets_channel_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_channel_id_idx');
            $table->dropConstrainedForeignId('channel_id');
        });

        Schema::dropIfExists('ticket_channels');
    }
};
