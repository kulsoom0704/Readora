<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use JsonException;
use RuntimeException;
use Throwable;

class ExportReadoraLegacyData extends Command
{
    protected $signature = 'readora:export-legacy-data {--output= : New JSON filename under storage/app}';

    protected $description = 'Export Readora stock and reservation records for the Inventory API cutover';

    /**
     * @throws JsonException
     */
    public function handle(): int
    {
        $output = $this->option('output');

        if (! is_string($output) || $output === '' || basename($output) !== $output || $output === '.' || $output === '..') {
            $this->error('Provide a plain new filename with --output, such as readora-legacy-cutover.json.');

            return self::FAILURE;
        }

        try {
            $this->assertSourceDatabaseIsReadora();

            $payload = DB::transaction(fn (): array => $this->exportPayload());
            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $outputPath = storage_path('app/'.$output);
            $file = @fopen($outputPath, 'x');

            if ($file === false) {
                throw new RuntimeException('The export file could not be created. It may already exist or the directory may not be writable.');
            }

            try {
                if (fwrite($file, $json) !== strlen($json)) {
                    throw new RuntimeException('The export file could not be written completely.');
                }
            } finally {
                fclose($file);
            }
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Export created: '.$outputPath);
        $this->line(count($payload['books']).' books and '.count($payload['reservations']).' reservations exported. No user names or email addresses were included.');

        return self::SUCCESS;
    }

    /** @return array{format: string, version: int, user_ids: list<int>, books: list<array{id: int, stock: int|null}>, reservations: list<array{id: int, user_id: int, book_id: int, status: string, created_at: string|null, updated_at: string|null}>} */
    private function exportPayload(): array
    {
        $books = DB::table('books')->orderBy('id')->get(['id', 'stock']);
        $bookIds = $books->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $reservations = DB::table('reservations')->orderBy('id')->get([
            'id',
            'user_id',
            'book_id',
            'status',
            'created_at',
            'updated_at',
        ]);
        $userIds = $reservations->pluck('user_id')->map(fn ($id): int => (int) $id)->unique()->values()->all();
        sort($userIds);
        $existingUserIds = DB::table('users')->whereIn('id', $userIds)->pluck('id')->map(fn ($id): int => (int) $id)->all();
        sort($existingUserIds);
        $allowedStatuses = ['active', 'cancelled', 'purchased'];
        $bookIdSet = array_fill_keys($bookIds, true);
        $userIdSet = array_fill_keys($existingUserIds, true);

        if (count($existingUserIds) !== count($userIds)) {
            throw new RuntimeException('A reservation refers to a user that does not exist in Readora.');
        }

        foreach ($reservations as $reservation) {
            if (! isset($bookIdSet[(int) $reservation->book_id])) {
                throw new RuntimeException('A reservation refers to a book that does not exist in Readora.');
            }

            if (! isset($userIdSet[(int) $reservation->user_id])) {
                throw new RuntimeException('A reservation refers to a user that does not exist in Readora.');
            }

            if (! in_array($reservation->status, $allowedStatuses, true)) {
                throw new RuntimeException('A reservation has a status that the Inventory API cannot represent.');
            }
        }

        return [
            'format' => 'readora-legacy-cutover',
            'version' => 1,
            'user_ids' => $existingUserIds,
            'books' => $books->map(fn ($book): array => [
                'id' => (int) $book->id,
                'stock' => $book->stock === null ? null : (int) $book->stock,
            ])->all(),
            'reservations' => $reservations->map(fn ($reservation): array => [
                'id' => (int) $reservation->id,
                'user_id' => (int) $reservation->user_id,
                'book_id' => (int) $reservation->book_id,
                'status' => (string) $reservation->status,
                'created_at' => $reservation->created_at === null ? null : (string) $reservation->created_at,
                'updated_at' => $reservation->updated_at === null ? null : (string) $reservation->updated_at,
            ])->all(),
        ];
    }

    private function assertSourceDatabaseIsReadora(): void
    {
        if (app()->environment('testing') && config('database.default') === 'sqlite' && config('database.connections.sqlite.database') === ':memory:') {
            return;
        }

        $configuredDatabase = (string) config('database.connections.sqlite.database', '');
        $isAbsolute = str_starts_with($configuredDatabase, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $configuredDatabase) === 1;
        $configuredPath = realpath($isAbsolute ? $configuredDatabase : base_path($configuredDatabase));
        $expectedPath = realpath(database_path('database.sqlite'));

        if (
            config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.url')
            || $configuredPath === false
            || $expectedPath === false
            || strcasecmp($configuredPath, $expectedPath) !== 0
        ) {
            throw new RuntimeException('The export is restricted to Readora database/database.sqlite. No data was exported.');
        }
    }
}
