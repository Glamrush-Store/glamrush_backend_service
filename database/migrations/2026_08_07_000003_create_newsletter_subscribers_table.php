<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('email')->unique();
            $table->string('status', 20)->index();
            $table->string('source', 100)->nullable();
            $table->string('confirmation_token_hash', 64)->nullable()->unique();
            $table->string('unsubscribe_token_hash', 64)->unique();
            $table->timestampTz('confirmation_expires_at')->nullable();
            $table->timestampTz('consented_at')->nullable();
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('unsubscribed_at')->nullable();
            $table->string('consent_ip_hash', 64)->nullable();
            $table->string('consent_user_agent', 500)->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
