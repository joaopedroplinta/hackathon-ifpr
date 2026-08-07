<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('edition')->default(1);
            $table->string('status')->default('draft');
            $table->text('description')->nullable();

            // Todas as janelas de tempo do evento. Comparadas sempre com now()
            // no servidor -- ver .claude/rules/security.md.
            $table->timestampTz('registration_opens_at')->nullable();
            $table->timestampTz('registration_closes_at')->nullable();
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->timestampTz('submission_deadline')->nullable();
            $table->timestampTz('voting_opens_at')->nullable();
            $table->timestampTz('voting_closes_at')->nullable();

            // Nulo = resultados escondidos. Publicar é ação manual e explicita
            // do organizador -- PLANO.md secao 7.
            $table->timestampTz('results_published_at')->nullable();

            $table->unsignedTinyInteger('min_team_size')->default(2);
            $table->unsignedTinyInteger('max_team_size')->default(5);

            $table->timestampsTz();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
