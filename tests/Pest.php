<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** @return array{id: int, user_id: int, book_id: int, status: string, created_at: string, updated_at: string} */
function reservationApiRecord(
    int $id,
    int $userId,
    int $bookId,
    string $status = 'active',
    ?string $createdAt = null,
    ?string $updatedAt = null,
): array {
    $timestamp = $createdAt ?? now()->toISOString();

    return [
        'id' => $id,
        'user_id' => $userId,
        'book_id' => $bookId,
        'status' => $status,
        'created_at' => $timestamp,
        'updated_at' => $updatedAt ?? $timestamp,
    ];
}

/** @param list<array{id: int, user_id: int, book_id: int, status: string, created_at: string, updated_at: string}> $records
 * @return array{data: list<array{id: int, user_id: int, book_id: int, status: string, created_at: string, updated_at: string}>, meta: array{current_page: int, last_page: int, per_page: int, total: int}}
 */
function reservationApiList(array $records, int $lastPage = 1): array
{
    return [
        'data' => $records,
        'meta' => [
            'current_page' => 1,
            'last_page' => $lastPage,
            'per_page' => 50,
            'total' => count($records),
        ],
    ];
}

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
