<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->ulid('discount_code_id')->nullable()->after('status')->index();
            $table->string('discount_code', 64)->nullable()->after('discount_code_id');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('subtotal');
            $table->decimal('shipping_discount_amount', 12, 2)->default(0)->after('shipping_amount');
            $table->json('discount_snapshot')->nullable()->after('shipping_discount_amount');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('line_subtotal', 12, 2)->default(0)->after('quantity');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('line_subtotal');
        });

        DB::table('order_items')->update(['line_subtotal' => DB::raw('line_total')]);

        Schema::create('discount_redemptions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('discount_code_id')->index();
            $table->foreignUlid('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('customer_accounts')->nullOnDelete();
            $table->string('guest_id')->nullable()->index();
            $table->string('customer_key', 80)->index();
            $table->string('code', 64)->index();
            $table->string('type', 32);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('shipping_discount_amount', 12, 2)->default(0);
            $table->char('currency', 3);
            $table->string('status', 16)->default('reserved')->index();
            $table->json('snapshot');
            $table->timestampTz('reserved_at');
            $table->timestampTz('redeemed_at')->nullable();
            $table->timestampTz('released_at')->nullable();
            $table->timestampTz('expires_at')->nullable()->index();
            $table->timestampsTz();
            $table->index(['discount_code_id', 'status']);
            $table->index(['discount_code_id', 'customer_key', 'status'], 'discount_redemptions_customer_usage_idx');
        });

        if (Schema::hasTable('discount_codes')) {
            Schema::table('orders', fn (Blueprint $table) => $table->foreign('discount_code_id')->references('id')->on('discount_codes')->nullOnDelete());
            Schema::table('discount_redemptions', fn (Blueprint $table) => $table->foreign('discount_code_id')->references('id')->on('discount_codes')->restrictOnDelete());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_redemptions');
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn(['line_subtotal', 'discount_amount']));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn([
            'discount_code_id', 'discount_code', 'discount_amount', 'shipping_discount_amount', 'discount_snapshot',
        ]));
    }
};
