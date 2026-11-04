<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique('cart_items_user_id_product_id_unique');
            $table->dropUnique('cart_items_cart_token_product_id_unique');
            $table->string('product_variant_id')->nullable()->after('product_id')->index();
        });

        DB::table('cart_items')
            ->select(['id', 'product_id'])
            ->orderBy('id')
            ->chunkById(200, function ($items): void {
                foreach ($items as $item) {
                    $variantId = DB::table('product_variants')
                        ->where('product_id', $item->product_id)
                        ->orderByDesc('is_default')
                        ->orderBy('sort_order')
                        ->value('id');

                    if (! $variantId) {
                        throw new \RuntimeException(
                            "Cannot migrate cart item {$item->id}: product {$item->product_id} has no variant."
                        );
                    }

                    DB::table('cart_items')
                        ->where('id', $item->id)
                        ->update(['product_variant_id' => $variantId]);
                }
            });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->string('product_variant_id')->nullable(false)->change();
            $table->unique(['user_id', 'product_variant_id']);
            $table->unique(['cart_token', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        $hasDuplicateProducts = DB::table('cart_items')
            ->selectRaw('user_id, cart_token, product_id, COUNT(*) as item_count')
            ->groupBy('user_id', 'cart_token', 'product_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateProducts) {
            throw new \RuntimeException(
                'Cannot roll back variant-aware carts while a cart contains multiple variants of one product.'
            );
        }

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique('cart_items_user_id_product_variant_id_unique');
            $table->dropUnique('cart_items_cart_token_product_variant_id_unique');
            $table->dropIndex('cart_items_product_variant_id_index');
            $table->dropColumn('product_variant_id');
            $table->unique(['user_id', 'product_id']);
            $table->unique(['cart_token', 'product_id']);
        });
    }
};
