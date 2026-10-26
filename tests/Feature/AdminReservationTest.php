<?php

use App\Models\Book;
use App\Models\Category;
use App\Models\User;

function createReservationForAdmin(string $status = 'active'): array
{
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();
    $customer = User::factory()->create();
    $category = Category::create(['name' => 'Fiction']);
    $book = Book::create([
        'title' => 'A Useful Book',
        'author' => 'A. Writer',
        'description' => 'A useful description.',
        'isbn' => '0001234567890',
        'price' => '19.95',
        'stock' => 4,
        'category_id' => $category->id,
    ]);
    $reservation = $customer->reservations()->create(['book_id' => $book->id]);

    if ($status !== 'active') {
        $reservation->forceFill(['status' => $status])->save();
    }

    return [$admin, $customer, $book, $reservation];
}

test('an admin can mark an active reservation as purchased without changing stock', function () {
    [$admin, , $book, $reservation] = createReservationForAdmin();

    $this->actingAs($admin)
        ->patch(route('admin.reservations.purchase', $reservation))
        ->assertRedirect(route('admin.reservations.index'))
        ->assertSessionHas('success');

    expect($reservation->fresh()->status)->toBe('purchased')
        ->and($book->fresh()->stock)->toBe(4);
});

test('a non-admin cannot mark a reservation as purchased', function () {
    [, $customer, $book, $reservation] = createReservationForAdmin();

    $this->actingAs($customer)
        ->patch(route('admin.reservations.purchase', $reservation))
        ->assertRedirect('/');

    expect($reservation->fresh()->status)->toBe('active')
        ->and($book->fresh()->stock)->toBe(4);
});

test('a cancelled reservation cannot be marked as purchased', function () {
    [$admin, , $book, $reservation] = createReservationForAdmin('cancelled');

    $this->actingAs($admin)
        ->patch(route('admin.reservations.purchase', $reservation))
        ->assertRedirect(route('admin.reservations.index'))
        ->assertSessionHas('info');

    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($book->fresh()->stock)->toBe(4);
});
