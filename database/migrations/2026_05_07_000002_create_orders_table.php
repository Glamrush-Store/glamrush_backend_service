<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('customer_accounts')->nullOnDelete();
            $table->string('guest_id')->nullable()->index();
            $table->string('order_number')->unique();
            $table->enum('status', ['pending_payment', 'pending_on_delivery', 'paid', 'processing', 'shipped', 'completed', 'cancelled', 'failed', 'refunded'])->default('pending_payment')->index();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('shipping_rate_id', 26)->nullable()->index();
            $table->string('shipping_method_name');
            $table->string('shipping_zone_name');
            $table->json('shipping_address');
            $table->json('billing_address')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->foreign('shipping_rate_id')->references('id')->on('shipping_rates')->nullOnDelete();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
