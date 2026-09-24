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
        $schemaName = $this->argument('company');

        if ($this->option('step') !== null &&
            (!$this->option('rollback') || !ctype_digit($this->option('step')) || (int) $this->option('step') < 1)) {
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

        $this->runTenantMigrations();

        $this->info("Migrations executed successfully for schema: {$schemaName}");

        return self::SUCCESS;
    }

    protected function createSchemaIfNotExists(string $schemaName): void
    {
        $query = "CREATE SCHEMA IF NOT EXISTS {$schemaName}";
        DB::statement($query);

        DatabaseHelper::changeSchema(schema: $schemaName);

        $this->info("Schema {$schemaName} checked successfully.");
    }

    protected function runTenantMigrations(): void
    {
        Artisan::call('migrate', [
            '--path' => 'database/migrations/tenant',
            '--force' => true,  // Run migrations without asking for confirmation
        ]);

        $this->info(Artisan::output());
    }

    protected function createMigrationsTableIfNotExists(): void
    {
        $tableExists = DB::select("SELECT to_regclass('migrations') AS exists");

        if (empty($tableExists) || !$tableExists[0]->exists) {
            Artisan::call('migrate:install');
            $this->info('Migrations table created successfully.');
        }
    }

}
