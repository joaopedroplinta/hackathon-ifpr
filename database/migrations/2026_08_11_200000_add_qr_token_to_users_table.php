<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('qr_token')->nullable()->unique()->after('avatar_url');
        });

        // Backfill pra quem já existe -- gen_random_uuid() é nativo do
        // Postgres 13+, sem precisar da extensão pgcrypto.
        DB::statement('UPDATE users SET qr_token = gen_random_uuid() WHERE qr_token IS NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('qr_token')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }
};
