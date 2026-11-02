<?php

namespace App\Console\Commands;

use App\Helpers\DatabaseHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class TenantMigrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:tenant-migrations {company}
                            {--rollback : Roll back the tenant migrations}
                            {--step= : Number of migrations to roll back}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate or roll back tenant migrations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $schemaName = (string) $this->argument('company');

        if (! preg_match('/^[a-z][a-z0-9_]{0,62}$/', $schemaName)) {
            $this->error('The schema name must start with a lowercase letter and contain only lowercase letters, numbers and underscores (max 63 characters).');

            return self::FAILURE;
        }

        if ($this->option('step') !== null &&
            (! $this->option('rollback') || ! ctype_digit($this->option('step')) || (int) $this->option('step') < 1)) {
            $this->error('The --step option requires --rollback and a positive integer.');

            return self::FAILURE;
        }

        if ($this->option('rollback')) {
            DatabaseHelper::changeSchema(schema: $schemaName);

            $options = [
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ];

            if ($this->option('step') !== null) {
                $options['--step'] = (int) $this->option('step');
            }

            $exitCode = Artisan::call('migrate:rollback', $options);
            $this->output->write(Artisan::output());

            if ($exitCode === self::SUCCESS) {
                $this->info("Migrations rolled back successfully for schema: {$schemaName}");
            }

            return $exitCode;
        }

        $this->createSchemaIfNotExists($schemaName);

        $this->createMigrationsTableIfNotExists();

        $exitCode = $this->runTenantMigrations();

        if ($exitCode !== self::SUCCESS) {
            $this->error("Failed to execute migrations for schema: {$schemaName}");

            return $exitCode;
        }

        $this->info("Migrations executed successfully for schema: {$schemaName}");

        return self::SUCCESS;
    }

    protected function createSchemaIfNotExists(string $schemaName): void
    {
        $quotedSchema = '"'.str_replace('"', '""', $schemaName).'"';
        $query = "CREATE SCHEMA IF NOT EXISTS {$quotedSchema}";
        DB::statement($query);

        DatabaseHelper::changeSchema(schema: $schemaName);

        $this->info("Schema {$schemaName} checked successfully.");
    }

    protected function runTenantMigrations(): int
    {
        $exitCode = Artisan::call('migrate', [
            '--path' => 'database/migrations/tenant',
            '--force' => true,  // Run migrations without asking for confirmation
        ]);

        $this->info(Artisan::output());

        return $exitCode;
    }

    protected function createMigrationsTableIfNotExists(): void
    {
        $tableExists = DB::select("SELECT to_regclass('migrations') AS exists");

        if (empty($tableExists) || ! $tableExists[0]->exists) {
            Artisan::call('migrate:install');
            $this->info('Migrations table created successfully.');
        }
    }
}
