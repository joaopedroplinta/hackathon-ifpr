<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('track_id')->nullable()->constrained()->nullOnDelete();

            // O lider e registro historico: apagar o usuario nao pode apagar
            // a equipe junto -- ver .claude/rules/database.md.
            $table->foreignId('leader_id')->constrained('users')->restrictOnDelete();

            $table->string('name', 60);
            $table->string('slug');
            $table->string('invite_code', 8)->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampsTz();

            // Organizador que apaga por engano as 23h precisa de desfazer.
            $table->softDeletesTz();

            // Nome e slug sao unicos POR EVENTO, nao globalmente.
            $table->unique(['event_id', 'name']);
            $table->unique(['event_id', 'slug']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
