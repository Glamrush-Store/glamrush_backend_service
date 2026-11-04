<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('idempotency_owner', 128)->nullable()->after('guest_id');
            $table->string('idempotency_key', 100)->nullable()->after('idempotency_owner');
            $table->string('idempotency_request_hash', 64)->nullable()->after('idempotency_key');
            $table->timestamp('inventory_committed_at')->nullable()->after('paid_at');
            $table->unique(['idempotency_owner', 'idempotency_key'], 'orders_idempotency_unique');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('idempotency_owner', 128)->nullable()->after('order_id');
            $table->string('idempotency_key', 100)->nullable()->after('idempotency_owner');
            $table->string('idempotency_request_hash', 64)->nullable()->after('idempotency_key');
            $table->unique(['idempotency_owner', 'idempotency_key'], 'payments_idempotency_unique');
            $table->unique(['provider', 'transaction_id'], 'payments_provider_transaction_unique');
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('event_key', 191)->nullable()->after('payment_id')->unique();
        });

        Schema::create('outbox_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('event_key', 191)->unique();
            $table->string('type');
            $table->json('payload');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable()->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropUnique(['event_key']);
            $table->dropColumn('event_key');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_provider_transaction_unique');
            $table->dropUnique('payments_idempotency_unique');
            $table->dropColumn(['idempotency_owner', 'idempotency_key', 'idempotency_request_hash']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_idempotency_unique');
            $table->dropColumn([
                'idempotency_owner',
                'idempotency_key',
                'idempotency_request_hash',
                'inventory_committed_at',
            ]);
        });
    }
};
