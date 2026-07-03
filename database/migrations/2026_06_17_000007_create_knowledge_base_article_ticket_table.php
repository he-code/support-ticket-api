<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_base_article_ticket', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_base_article_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attached_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['knowledge_base_article_id', 'ticket_id'], 'kb_article_ticket_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_base_article_ticket');
    }
};
