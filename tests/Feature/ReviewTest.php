<?php

use App\Models\Book;
use App\Models\Category;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\QueryException;

function createBookForReview(): Book
{
    $category = Category::create(['name' => 'Fiction']);

    return Book::create([
        'title' => 'A Reviewed Book',
        'author' => 'A. Writer',
        'description' => 'A book for review tests.',
        'isbn' => '0001234567890',
        'price' => '19.95',
        'stock' => 1,
        'category_id' => $category->id,
    ]);
}

function createPurchasedReservation(User $user, Book $book): Reservation
{
    $reservation = $user->reservations()->create(['book_id' => $book->id]);
    $reservation->forceFill(['status' => 'purchased'])->save();

    return $reservation;
}

test('a user without a completed purchase cannot submit a review', function () {
    $user = User::factory()->create();
    $book = createBookForReview();
    $user->reservations()->create(['book_id' => $book->id]);

    $this->actingAs($user)
        ->get('/books/'.$book->id)
        ->assertOk()
        ->assertSee('No reviews yet.')
        ->assertDontSee('Write a review');

    $this->actingAs($user)
        ->post(route('books.reviews.store', $book), [
            'score' => 5,
            'body' => 'A useful review.',
        ])
        ->assertForbidden();

    expect(Review::query()->count())->toBe(0);
});

test('a guest cannot submit a review', function () {
    $book = createBookForReview();

    $this->post(route('books.reviews.store', $book), [
        'score' => 5,
        'body' => 'A useful review.',
    ])->assertRedirect(route('login'));

    expect(Review::query()->count())->toBe(0);
});

test('a user with a purchased reservation can submit a score and written review', function () {
    $user = User::factory()->create();
    $book = createBookForReview();
    createPurchasedReservation($user, $book);

    $this->actingAs($user)
        ->get('/books/'.$book->id)
        ->assertOk()
        ->assertSee('Write a review')
        ->assertSee('x-data="{ rating: 0 }"', false)
        ->assertSee('x-model.number="rating"', false)
        ->assertSee('for="score-3"', false)
        ->assertSee('x-on:click="rating = 3"', false)
        ->assertSee('type="radio"', false)
        ->assertSee('name="score"', false)
        ->assertSee('value="5"', false);

    $this->actingAs($user)
        ->post(route('books.reviews.store', $book), [
            'score' => 5,
            'body' => 'A helpful and enjoyable book.',
        ])
        ->assertRedirect('/books/'.$book->id)
        ->assertSessionHas('review_success');

    $this->assertDatabaseHas('reviews', [
        'user_id' => $user->id,
        'book_id' => $book->id,
        'score' => 5,
        'body' => 'A helpful and enjoyable book.',
    ]);
});

test('a user cannot submit a second review for the same book', function () {
    $user = User::factory()->create();
    $book = createBookForReview();
    createPurchasedReservation($user, $book);
    Review::create([
        'user_id' => $user->id,
        'book_id' => $book->id,
        'score' => 4,
        'body' => 'My first review.',
    ]);

    $this->actingAs($user)
        ->post(route('books.reviews.store', $book), [
            'score' => 3,
            'body' => 'A duplicate review.',
        ])
        ->assertSessionHasErrors('book_id');

    expect(Review::query()->count())->toBe(1);
});

test('the database also prevents duplicate reviews for a user and book', function () {
    $user = User::factory()->create();
    $book = createBookForReview();
    Review::create([
        'user_id' => $user->id,
        'book_id' => $book->id,
        'score' => 4,
        'body' => 'My first review.',
    ]);

    expect(fn () => Review::create([
        'user_id' => $user->id,
        'book_id' => $book->id,
        'score' => 3,
        'body' => 'A duplicate review.',
    ]))->toThrow(QueryException::class);

    expect(Review::query()->count())->toBe(1);
});

test('a review requires a score from one to five and written text', function () {
    $user = User::factory()->create();
    $book = createBookForReview();
    createPurchasedReservation($user, $book);

    $this->actingAs($user)
        ->post(route('books.reviews.store', $book), [
            'score' => 6,
            'body' => '',
        ])
        ->assertSessionHasErrors(['score', 'body']);

    expect(Review::query()->count())->toBe(0);
});

test('book details display existing reviews and hide the form after a review is submitted', function () {
    $user = User::factory()->create();
    $book = createBookForReview();
    Review::create([
        'user_id' => $user->id,
        'book_id' => $book->id,
        'score' => 4,
        'body' => 'A clear and enjoyable read.',
    ]);

    $this->actingAs($user)
        ->get('/books/'.$book->id)
        ->assertOk()
        ->assertSee('Reviews')
        ->assertSee($user->name)
        ->assertSee('★★★★☆')
        ->assertSee('4.0/5')
        ->assertSee('(1 review)')
        ->assertSee('A clear and enjoyable read.')
        ->assertSee('You have already reviewed this book.')
        ->assertDontSee('Write a review');
});

test('book details calculate the average rating and count across saved reviews', function () {
    $firstReviewer = User::factory()->create();
    $secondReviewer = User::factory()->create();
    $book = createBookForReview();

    Review::create([
        'user_id' => $firstReviewer->id,
        'book_id' => $book->id,
        'score' => 4,
        'body' => 'A thoughtful review.',
    ]);
    Review::create([
        'user_id' => $secondReviewer->id,
        'book_id' => $book->id,
        'score' => 5,
        'body' => 'An excellent review.',
    ]);

    $this->get('/books/'.$book->id)
        ->assertOk()
        ->assertSee('4.5/5')
        ->assertSee('(2 reviews)')
        ->assertSee('★★★★☆')
        ->assertSee('★★★★★');
});
