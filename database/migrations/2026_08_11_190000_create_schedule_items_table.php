<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();

            // Nullable: nem toda palestra ou refeição pertence a uma trilha.
            $table->foreignId('track_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->string('type');

            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');

            $table->string('location', 120)->nullable();
            $table->string('speaker_name', 120)->nullable();
            $table->text('speaker_bio')->nullable();

            // Rascunho por padrão: o organizador monta a agenda aos poucos e
            // só ativa is_published quando ela está pronta pro público ver.
            $table->boolean('is_published')->default(false);

            $table->timestampsTz();

            $table->index('event_id');
            $table->index(['event_id', 'is_published']);
            $table->index('starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_items');
    }
};
