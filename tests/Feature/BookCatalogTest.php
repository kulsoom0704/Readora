<?php

use App\Models\Book;
use App\Models\Category;
use App\Models\User;

function createAdminUserAndCategory(): array
{
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();

    return [$admin, Category::create(['name' => 'Fiction'])];
}

test('admin can create a book with an ISBN and a two-decimal price', function () {
    [$admin, $category] = createAdminUserAndCategory();

    $response = $this->actingAs($admin)->post(route('admin.books.store'), [
        'title' => 'A Useful Book',
        'author' => 'A. Writer',
        'description' => 'A useful description.',
        'isbn' => '0001234567890',
        'price' => '19.95',
        'category_id' => $category->id,
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect('/admin/books');

    $book = Book::query()->where('isbn', '0001234567890')->firstOrFail();

    expect($book->isbn)->toBe('0001234567890')
        ->and($book->price)->toBe('19.95');
});

test('admin cannot create a book with an ISBN already in use', function () {
    [$admin, $category] = createAdminUserAndCategory();

    Book::create([
        'title' => 'Existing Book',
        'author' => 'A. Writer',
        'description' => 'An existing book.',
        'isbn' => '0001234567890',
        'price' => '10.00',
        'category_id' => $category->id,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.books.store'), [
        'title' => 'Another Useful Book',
        'author' => 'B. Writer',
        'description' => 'Another description.',
        'isbn' => '0001234567890',
        'price' => '12.50',
        'category_id' => $category->id,
    ]);

    $response->assertSessionHasErrors('isbn');
    expect(Book::query()->count())->toBe(1);
});

test('admin cannot set a negative book price', function () {
    [$admin, $category] = createAdminUserAndCategory();

    $response = $this->actingAs($admin)->post(route('admin.books.store'), [
        'title' => 'A Useful Book',
        'author' => 'A. Writer',
        'description' => 'A useful description.',
        'isbn' => '0001234567890',
        'price' => '-0.01',
        'category_id' => $category->id,
    ]);

    $response->assertSessionHasErrors('price');
    expect(Book::query()->count())->toBe(0);
});

test('admin can update a book without changing its ISBN', function () {
    [$admin, $category] = createAdminUserAndCategory();

    $book = Book::create([
        'title' => 'Existing Book',
        'author' => 'A. Writer',
        'description' => 'An existing book.',
        'isbn' => '0001234567890',
        'price' => '10.00',
        'category_id' => $category->id,
    ]);

    $response = $this->actingAs($admin)->put(route('admin.books.update', $book), [
        'title' => 'Existing Book Updated',
        'author' => 'A. Writer',
        'description' => 'An updated description.',
        'isbn' => '0001234567890',
        'price' => '23.50',
        'category_id' => $category->id,
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect('/admin/books');
    expect($book->fresh()->isbn)->toBe('0001234567890')
        ->and($book->fresh()->price)->toBe('23.50');
});

test('book detail displays its ISBN and price', function () {
    [, $category] = createAdminUserAndCategory();

    $book = Book::create([
        'title' => 'A Useful Book',
        'author' => 'A. Writer',
        'description' => 'A useful description.',
        'isbn' => '0001234567890',
        'price' => '19.95',
        'category_id' => $category->id,
    ]);

    $this->get('/books/'.$book->id)
        ->assertOk()
        ->assertSee('0001234567890')
        ->assertSee('€19.95');
});
