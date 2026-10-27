<?php

use App\Models\Inventory;
use App\Models\Reservation;
use App\Services\ReadoraLegacyDataImporter;
use Illuminate\Support\Facades\DB;

function readoraLegacyImportPayload(): array
{
    return [
        'format' => 'readora-legacy-cutover',
        'version' => 1,
        'user_ids' => [90],
        'books' => [
            ['id' => 701, 'stock' => 8],
            ['id' => 702, 'stock' => null],
        ],
        'reservations' => [
            [
                'id' => 801,
                'user_id' => 90,
                'book_id' => 701,
                'status' => 'cancelled',
                'created_at' => '2026-10-20 10:11:12',
                'updated_at' => '2026-10-21 13:14:15',
            ],
            [
                'id' => 802,
                'user_id' => 90,
                'book_id' => 701,
                'status' => 'purchased',
                'created_at' => '2026-10-22 16:17:18',
                'updated_at' => '2026-10-23 19:20:21',
            ],
        ],
    ];
}

it('imports exact stock and reservation history without applying stock movements', function () {
    $result = app(ReadoraLegacyDataImporter::class)->import(readoraLegacyImportPayload(), dryRun: false);

    expect($result['inventories_inserted'])->toBe(2)
        ->and($result['reservations_inserted'])->toBe(2)
        ->and(Inventory::where('book_id', 701)->value('stock'))->toBe(8)
        ->and(Inventory::where('book_id', 702)->value('stock'))->toBeNull()
        ->and(Reservation::find(801)->status)->toBe('cancelled')
        ->and(Reservation::find(802)->status)->toBe('purchased')
        ->and(Reservation::find(801)->user_id)->toBe(90)
        ->and(Reservation::find(801)->book_id)->toBe(701)
        ->and(Reservation::find(801)->idempotency_key)->toBe('legacy-readora-reservation-801')
        ->and(Reservation::find(801)->created_at->format('Y-m-d H:i:s'))->toBe('2026-10-20 10:11:12')
        ->and(Reservation::find(801)->updated_at->format('Y-m-d H:i:s'))->toBe('2026-10-21 13:14:15');
});

it('reports a dry run without writing records', function () {
    $result = app(ReadoraLegacyDataImporter::class)->import(readoraLegacyImportPayload(), dryRun: true);

    expect($result['dry_run'])->toBeTrue()
        ->and($result['inventories_inserted'])->toBe(2)
        ->and($result['reservations_inserted'])->toBe(2)
        ->and(Inventory::count())->toBe(0)
        ->and(Reservation::count())->toBe(0);
});

it('allows books to be imported when the export has no reservation history yet', function () {
    $payload = readoraLegacyImportPayload();
    $payload['user_ids'] = [];
    $payload['reservations'] = [];

    $result = app(ReadoraLegacyDataImporter::class)->import($payload, dryRun: false);

    expect($result['inventories_inserted'])->toBe(2)
        ->and($result['reservations_inserted'])->toBe(0)
        ->and(Inventory::count())->toBe(2)
        ->and(Reservation::count())->toBe(0);
});

it('makes the Artisan import dry-run by default and applies only with the explicit apply option', function () {
    $path = tempnam(sys_get_temp_dir(), 'readora-cutover-');
    file_put_contents($path, json_encode(readoraLegacyImportPayload(), JSON_THROW_ON_ERROR));

    try {
        $this->artisan('inventory:import-readora-legacy', ['file' => $path])
            ->expectsOutput('DRY RUN: no records were written.')
            ->assertExitCode(0);

        expect(Inventory::count())->toBe(0)
            ->and(Reservation::count())->toBe(0);

        $this->artisan('inventory:import-readora-legacy', ['file' => $path, '--apply' => true])
            ->expectsOutput('Legacy data import completed.')
            ->assertExitCode(0);

        expect(Inventory::count())->toBe(2)
            ->and(Reservation::count())->toBe(2);
    } finally {
        if (is_file($path)) {
            unlink($path);
        }
    }
});

it('accepts an identical rerun without creating duplicate records', function () {
    $importer = app(ReadoraLegacyDataImporter::class);
    $payload = readoraLegacyImportPayload();

    $first = $importer->import($payload, dryRun: false);
    $second = $importer->import($payload, dryRun: false);

    expect($first['inventories_inserted'])->toBe(2)
        ->and($first['reservations_inserted'])->toBe(2)
        ->and($second['inventories_inserted'])->toBe(0)
        ->and($second['inventories_unchanged'])->toBe(2)
        ->and($second['reservations_inserted'])->toBe(0)
        ->and($second['reservations_unchanged'])->toBe(2)
        ->and(Inventory::count())->toBe(2)
        ->and(Reservation::count())->toBe(2);
});

it('rejects conflicting inventory without partially importing other records', function () {
    DB::table('inventories')->insert([
        'book_id' => 701,
        'stock' => 99,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => app(ReadoraLegacyDataImporter::class)->import(readoraLegacyImportPayload(), dryRun: false))
        ->toThrow(RuntimeException::class, 'inventory for book 701 differs');

    expect(Inventory::count())->toBe(1)
        ->and(Inventory::where('book_id', 702)->exists())->toBeFalse()
        ->and(Reservation::count())->toBe(0);
});

it('rejects a conflicting reservation without overwriting its history', function () {
    DB::table('inventories')->insert([
        ['book_id' => 701, 'stock' => 8, 'created_at' => now(), 'updated_at' => now()],
        ['book_id' => 702, 'stock' => null, 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('reservations')->insert([
        'id' => 801,
        'user_id' => 90,
        'book_id' => 701,
        'status' => 'purchased',
        'idempotency_key' => 'legacy-readora-reservation-801',
        'created_at' => '2026-10-20 10:11:12',
        'updated_at' => '2026-10-21 13:14:15',
    ]);

    expect(fn () => app(ReadoraLegacyDataImporter::class)->import(readoraLegacyImportPayload(), dryRun: false))
        ->toThrow(RuntimeException::class, 'reservation 801 differs');

    expect(Reservation::find(801)->status)->toBe('purchased')
        ->and(Reservation::whereKey(802)->exists())->toBeFalse();
});

it('rejects reservations that refer to users absent from the export', function () {
    $payload = readoraLegacyImportPayload();
    $payload['user_ids'] = [91];

    expect(fn () => app(ReadoraLegacyDataImporter::class)->import($payload, dryRun: false))
        ->toThrow(RuntimeException::class, 'refers to a user absent from the export');

    expect(Inventory::count())->toBe(0)
        ->and(Reservation::count())->toBe(0);
});
