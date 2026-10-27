<?php

namespace App\Console\Commands;

use App\Services\ReadoraLegacyDataImporter;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ImportReadoraLegacyData extends Command
{
    protected $signature = 'inventory:import-readora-legacy {file : Path to the Readora JSON export} {--dry-run : Validate and report only (the default)} {--apply : Persist the validated records}';

    protected $description = 'Safely import Readora stock and reservation history into the Inventory API';

    public function handle(ReadoraLegacyDataImporter $importer): int
    {
        if ($this->option('dry-run') && $this->option('apply')) {
            $this->error('Choose either --dry-run or --apply, not both.');

            return self::FAILURE;
        }

        try {
            $this->assertTargetDatabaseIsInventoryApi();

            $inputPath = $this->resolveInputPath((string) $this->argument('file'));
            $contents = @file_get_contents($inputPath);

            if ($contents === false) {
                throw new RuntimeException('The Readora export file could not be read.');
            }

            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($payload)) {
                throw new RuntimeException('The Readora export must contain a JSON object.');
            }

            $dryRun = ! $this->option('apply');
            $result = $importer->import($payload, $dryRun);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->error($field.': '.$message);
                }
            }

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($dryRun ? 'DRY RUN: no records were written.' : 'Legacy data import completed.');
        $this->line('Inventories: '.$result['inventories_inserted'].' to insert, '.$result['inventories_unchanged'].' identical already present.');
        $this->line('Reservations: '.$result['reservations_inserted'].' to insert, '.$result['reservations_unchanged'].' identical already present.');

        return self::SUCCESS;
    }

    private function resolveInputPath(string $input): string
    {
        $isAbsolute = str_starts_with($input, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $input) === 1;
        $path = $isAbsolute ? $input : base_path($input);
        $resolvedPath = realpath($path);

        if ($resolvedPath === false || ! is_file($resolvedPath) || ! is_readable($resolvedPath)) {
            throw new RuntimeException('The Readora export file does not exist or cannot be read.');
        }

        return $resolvedPath;
    }

    private function assertTargetDatabaseIsInventoryApi(): void
    {
        $configuredDatabase = (string) config('database.connections.sqlite.database', '');

        if (app()->environment('testing') && config('database.default') === 'sqlite' && $configuredDatabase === ':memory:') {
            return;
        }

        $isAbsolute = str_starts_with($configuredDatabase, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $configuredDatabase) === 1;
        $configuredPath = realpath($isAbsolute ? $configuredDatabase : base_path($configuredDatabase));
        $expectedPath = realpath(database_path('inventory.sqlite'));

        if (
            config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.url')
            || $configuredPath === false
            || $expectedPath === false
            || strcasecmp($configuredPath, $expectedPath) !== 0
        ) {
            throw new RuntimeException('Import is restricted to inventory-api/database/inventory.sqlite. No API records were changed.');
        }
    }
}
