<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumed_events', function (Blueprint $table): void {
            $table->string('id', 100)->primary();
            $table->string('type', 100)->index();
            $table->timestampTz('processed_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumed_events');
    }
};
