<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('cart_token', 36)->nullable()->index();
            $table->string('product_id');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
            $table->unique(['cart_token', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
