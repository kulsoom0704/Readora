<?php

use App\Models\Book;
use App\Models\Category;
use App\Models\Reservation;
use App\Models\User;

function createBookForReservation(
    ?int $stock,
    string $title = 'A Useful Book',
    string $isbn = '0001234567890',
    ?string $image = null
): Book {
    $category = Category::create(['name' => 'Fiction']);

    return Book::create([
        'title' => $title,
        'author' => 'A. Writer',
        'description' => 'A useful description.',
        'isbn' => $isbn,
        'price' => '19.95',
        'stock' => $stock,
        'image' => $image,
        'category_id' => $category->id,
    ]);
}

test('an authenticated user can reserve one copy and receives pickup and payment information', function () {
    $user = User::factory()->create();
    $book = createBookForReservation(2);

    $response = $this->actingAs($user)->post(route('books.reservations.store', $book));

    $reservation = Reservation::query()->firstOrFail();

    $response->assertRedirect(route('reservations.confirmation', $reservation));
    $this->assertDatabaseHas('reservations', [
        'id' => $reservation->id,
        'user_id' => $user->id,
        'book_id' => $book->id,
    ]);
    expect($book->fresh()->stock)->toBe(1);

    $this->actingAs($user)
        ->get(route('reservations.confirmation', $reservation))
        ->assertOk()
        ->assertSee('Reservation confirmed')
        ->assertSee('Contact Readora to arrange pickup and payment.');
});

test('a user cannot reserve a book when stock is insufficient', function () {
    $user = User::factory()->create();
    $book = createBookForReservation(0);

    $this->actingAs($user)
        ->from('/books/'.$book->id)
        ->post(route('books.reservations.store', $book))
        ->assertRedirect('/books/'.$book->id)
        ->assertSessionHasErrors('reservation');

    expect($book->fresh()->stock)->toBe(0)
        ->and(Reservation::query()->count())->toBe(0);
});

test('a second reservation cannot over-reserve the last copy', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $book = createBookForReservation(1);

    $this->actingAs($firstUser)
        ->post(route('books.reservations.store', $book))
        ->assertRedirect();

    $this->actingAs($secondUser)
        ->from('/books/'.$book->id)
        ->post(route('books.reservations.store', $book))
        ->assertRedirect('/books/'.$book->id)
        ->assertSessionHasErrors('reservation');

    expect($book->fresh()->stock)->toBe(0)
        ->and(Reservation::query()->count())->toBe(1);
});

test('a book with unknown stock cannot be reserved', function () {
    $user = User::factory()->create();
    $book = createBookForReservation(null);

    $this->actingAs($user)
        ->from('/books/'.$book->id)
        ->post(route('books.reservations.store', $book))
        ->assertRedirect('/books/'.$book->id)
        ->assertSessionHasErrors('reservation');

    expect($book->fresh()->stock)->toBeNull()
        ->and(Reservation::query()->count())->toBe(0);
});

test('a guest must log in before reserving a book', function () {
    $book = createBookForReservation(1);

    $this->post(route('books.reservations.store', $book))
        ->assertRedirect(route('login'));

    expect($book->fresh()->stock)->toBe(1)
        ->and(Reservation::query()->count())->toBe(0);
});

test('only the reserving user can view a reservation confirmation', function () {
    $owner = User::factory()->create();
    $anotherUser = User::factory()->create();
    $book = createBookForReservation(1);
    $reservation = $owner->reservations()->create(['book_id' => $book->id]);

    $this->actingAs($anotherUser)
        ->get(route('reservations.confirmation', $reservation))
        ->assertForbidden();
});

test('an authenticated user sees only their reservations with book and reservation details', function () {
    $user = User::factory()->create();
    $anotherUser = User::factory()->create();
    $ownBook = createBookForReservation(1, 'My Reserved Book', '0001111111111', 'storage/books/my-book.jpg');
    $anotherBook = createBookForReservation(1, 'Someone Elses Book', '0002222222222');
    $ownReservation = $user->reservations()->create(['book_id' => $ownBook->id]);
    $anotherUser->reservations()->create(['book_id' => $anotherBook->id]);

    $this->actingAs($user)
        ->get(route('reservations.index'))
        ->assertOk()
        ->assertSee('My Reservations')
        ->assertSee('My Reserved Book')
        ->assertSee('/storage/books/my-book.jpg')
        ->assertSee('Reservation #'.$ownReservation->id)
        ->assertSee($ownReservation->created_at->format('F j, Y'))
        ->assertSee('Reserved')
        ->assertSee('Cancel Reservation')
        ->assertDontSee('Someone Elses Book');
});

test('a guest must sign in before viewing their reservations', function () {
    $this->get(route('reservations.index'))
        ->assertRedirect(route('login'));
});

test('cancelling a reservation marks it cancelled and restores one copy to stock', function () {
    $user = User::factory()->create();
    $book = createBookForReservation(0);
    $reservation = $user->reservations()->create(['book_id' => $book->id]);

    $this->actingAs($user)
        ->delete(route('reservations.cancel', $reservation))
        ->assertRedirect(route('reservations.index'))
        ->assertSessionHas('success');

    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($book->fresh()->stock)->toBe(1);

    $this->actingAs($user)
        ->get(route('reservations.index'))
        ->assertOk()
        ->assertSee('Cancelled')
        ->assertDontSee('Cancel Reservation');
});

test('cancelling the same reservation twice restores stock only once', function () {
    $user = User::factory()->create();
    $book = createBookForReservation(0);
    $reservation = $user->reservations()->create(['book_id' => $book->id]);

    $this->actingAs($user)->delete(route('reservations.cancel', $reservation));
    $this->actingAs($user)
        ->delete(route('reservations.cancel', $reservation))
        ->assertRedirect(route('reservations.index'))
        ->assertSessionHas('info');

    expect($reservation->fresh()->status)->toBe('cancelled')
        ->and($book->fresh()->stock)->toBe(1);
});

test('a user cannot cancel another users reservation', function () {
    $owner = User::factory()->create();
    $anotherUser = User::factory()->create();
    $book = createBookForReservation(0);
    $reservation = $owner->reservations()->create(['book_id' => $book->id]);

    $this->actingAs($anotherUser)
        ->delete(route('reservations.cancel', $reservation))
        ->assertForbidden();

    expect($reservation->fresh()->status)->toBe('active')
        ->and($book->fresh()->stock)->toBe(0);
});
