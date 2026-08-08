<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_submissions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('storefront_category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('customer_account_id')->nullable()->constrained('customer_accounts')->nullOnDelete();
            $table->string('name', 150);
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->string('subject', 180)->nullable();
            $table->text('message');
            $table->string('status', 20)->default('new')->index();
            $table->string('source', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->string('duplicate_fingerprint', 64);
            $table->string('deduplication_bucket', 12);
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampsTz();
            $table->unique(['duplicate_fingerprint', 'deduplication_bucket'], 'contact_submissions_dedup_unique');
            $table->index(['storefront_category_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_submissions');
    }
};
