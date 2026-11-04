<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setting_categories', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('setting_category_id')->constrained('setting_categories')->cascadeOnDelete();
            $table->string('key');
            $table->json('value')->nullable();
            $table->string('value_type', 32)->default('string');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['setting_category_id', 'key']);
            $table->index(['key', 'is_active']);
            $table->index(['is_public', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('setting_categories');
    }
};
