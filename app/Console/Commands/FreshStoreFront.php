<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FreshStoreFront extends Command
{
    protected $signature = 'freshStoreFront';

    protected $description = 'Drop only tables defined in the migrations folder, then re-run migrations';

    public function handle(): int
    {
        $migrationFiles = glob(database_path('migrations/*.php'));

        // Collect table names from Schema::create() calls
        $tables = [];
        $migrationNames = [];

        foreach ($migrationFiles as $file) {
            $content = file_get_contents($file);

            preg_match_all("/Schema::create\s*\(\s*['\"]([^'\"]+)['\"]/", $content, $matches);

            foreach ($matches[1] as $table) {
                $tables[] = $table;
            }

            $migrationNames[] = pathinfo($file, PATHINFO_FILENAME);
        }

        if (empty($tables)) {
            $this->warn('No Schema::create tables found in migration files.');
            return self::SUCCESS;
        }

        $this->info('Dropping tables: ' . implode(', ', $tables));

        Schema::disableForeignKeyConstraints(); // does not work for postgress
        foreach ($tables as $table) {
            //Schema::dropIfExists($table);
            DB::statement('DROP TABLE IF EXISTS "' . $table . '" CASCADE');
            $this->line("  <fg=red>Dropped:</> {$table}");
        }
        Schema::enableForeignKeyConstraints();

        // Remove migration records so migrate will re-run them
        if (Schema::hasTable('migrations')) {
            DB::table('migrations')
                ->whereIn('migration', $migrationNames)
                ->delete();
        }

        $this->newLine();
        $this->call('migrate');

        return self::SUCCESS;
    }
}
