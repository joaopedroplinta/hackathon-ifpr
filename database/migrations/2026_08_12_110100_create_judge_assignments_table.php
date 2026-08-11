<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('judge_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();

            $table->foreignId('judge_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();

            $table->string('status')->default('pending');
            $table->timestampTz('assigned_at');

            $table->timestampsTz();

            $table->index('event_id');
            $table->index('judge_id');
            $table->index('submission_id');

            // Mesmo jurado não pode receber a mesma submissão duas vezes --
            // nem na distribuição automática nem no ajuste manual.
            $table->unique(['judge_id', 'submission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('judge_assignments');
    }
};
