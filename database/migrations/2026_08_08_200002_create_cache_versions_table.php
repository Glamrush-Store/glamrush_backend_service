<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cache_versions', function (Blueprint $table): void {
            $table->string('namespace', 64)->primary();
            $table->unsignedBigInteger('version')->default(1);
            $table->timestampsTz();
        });

        $now = now();

        DB::table('cache_versions')->insert(array_map(
            fn (string $namespace): array => [
                'namespace' => $namespace,
                'version' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['catalog', 'homepage', 'payment-methods', 'shipping'],
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_versions');
    }
};
