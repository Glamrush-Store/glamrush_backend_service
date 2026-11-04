<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'inventory_released_at')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('inventory_released_at')->nullable()->after('inventory_committed_at')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'inventory_released_at')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('inventory_released_at');
        });
    }
};
