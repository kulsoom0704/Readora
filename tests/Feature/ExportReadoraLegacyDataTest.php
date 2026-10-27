<?php

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('exports stock and reservation references without personal user details', function () {
    $category = Category::create(['name' => 'Cutover Test']);
    $user = User::factory()->create();
    $stockedBook = Book::create([
        'title' => 'A Stocked Book',
        'author' => 'Test Author',
        'description' => 'Exporter test book.',
        'isbn' => '9912345678901',
        'price' => '12.50',
        'stock' => 5,
        'category_id' => $category->id,
    ]);
    $unknownBook = Book::create([
        'title' => 'An Unknown Book',
        'author' => 'Test Author',
        'description' => 'Exporter test book with unknown stock.',
        'isbn' => '9912345678902',
        'price' => '15.00',
        'stock' => null,
        'category_id' => $category->id,
    ]);
    $reservation = $user->reservations()->create(['book_id' => $stockedBook->id]);
    DB::table('reservations')->where('id', $reservation->id)->update([
        'status' => 'purchased',
        'created_at' => '2026-10-20 10:11:12',
        'updated_at' => '2026-10-21 13:14:15',
    ]);

    $filename = 'legacy-export-'.Str::uuid().'.json';
    $outputPath = storage_path('app/'.$filename);

    try {
        $this->artisan('readora:export-legacy-data', ['--output' => $filename])
            ->assertExitCode(0);

        $payload = json_decode(file_get_contents($outputPath), true, 512, JSON_THROW_ON_ERROR);

        expect($payload['format'])->toBe('readora-legacy-cutover')
            ->and($payload['version'])->toBe(1)
            ->and($payload['user_ids'])->toBe([$user->id])
            ->and($payload['books'])->toBe([
                ['id' => $stockedBook->id, 'stock' => 5],
                ['id' => $unknownBook->id, 'stock' => null],
            ])
            ->and($payload['reservations'][0])->toMatchArray([
                'id' => $reservation->id,
                'user_id' => $user->id,
                'book_id' => $stockedBook->id,
                'status' => 'purchased',
                'created_at' => '2026-10-20 10:11:12',
                'updated_at' => '2026-10-21 13:14:15',
            ])
            ->and(json_encode($payload))->not->toContain($user->email);
    } finally {
        if (is_file($outputPath)) {
            unlink($outputPath);
        }
    }
});

it('does not overwrite an existing export file', function () {
    $filename = 'legacy-export-'.Str::uuid().'.json';
    $outputPath = storage_path('app/'.$filename);
    file_put_contents($outputPath, 'keep this file');

    try {
        $this->artisan('readora:export-legacy-data', ['--output' => $filename])
            ->assertExitCode(1);

        expect(file_get_contents($outputPath))->toBe('keep this file');
    } finally {
        if (is_file($outputPath)) {
            unlink($outputPath);
        }
    }
});
