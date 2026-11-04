<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('product_id', 26)->index();
            $table->string('product_variant_id', 26)->nullable()->index();
            $table->string('product_name');
            $table->string('product_slug');
            $table->string('sku');
            $table->decimal('unit_price', 12, 2);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('line_total', 12, 2);
            $table->json('product_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
