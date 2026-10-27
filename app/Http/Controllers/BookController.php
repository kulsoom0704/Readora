<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Services\InventoryApiException;
use App\Services\InventoryReadService;
use App\Services\InventoryReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    // Public: show all books
    public function index()
    {
        $books = Book::all();

        return view('books.index', compact('books'));
    }

    public function show(
        Request $request,
        InventoryReadService $inventoryReader,
        InventoryReservationService $reservationReader,
        int $id,
    ) {
        $book = Book::query()
            ->with(['category', 'reviews.user'])
            ->findOrFail($id);
        $inventory = $inventoryReader->forBook((int) $book->getKey());

        $reviewCount = $book->reviews->count();
        $averageRating = $book->reviews->avg('score');
        $averageRatingStars = $averageRating === null ? 0 : (int) round($averageRating);
        $reservationIdempotencyKey = null;

        if ($request->user() !== null) {
            $idempotencySessionKey = 'inventory_reservation_keys.'.$request->user()->getAuthIdentifier().'.'.$book->getKey();
            $isRetryingReservation = (int) $request->session()->get('reservation_retry_book') === (int) $book->getKey()
                && (int) $request->session()->get('reservation_retry_user') === (int) $request->user()->getAuthIdentifier();
            $reservationIdempotencyKey = $isRetryingReservation
                ? $request->session()->get($idempotencySessionKey)
                : (string) Str::uuid();

            if (is_string($reservationIdempotencyKey) && $reservationIdempotencyKey !== '') {
                $request->session()->put($idempotencySessionKey, $reservationIdempotencyKey);
            } else {
                $reservationIdempotencyKey = (string) Str::uuid();
                $request->session()->put($idempotencySessionKey, $reservationIdempotencyKey);
            }
        }

        $hasReviewed = false;
        $canReview = false;
        $reviewEligibilityAvailable = true;

        if ($request->user()) {
            $hasReviewed = $request->user()->reviews()
                ->where('book_id', $book->getKey())
                ->exists();

            if (! $hasReviewed) {
                try {
                    $canReview = $reservationReader->userHasPurchasedBook(
                        $request->user(),
                        (int) $book->getKey(),
                    );
                } catch (InventoryApiException) {
                    $reviewEligibilityAvailable = false;
                }
            }
        }

        return view('books.show', compact(
            'book',
            'canReview',
            'hasReviewed',
            'reviewEligibilityAvailable',
            'reviewCount',
            'averageRating',
            'averageRatingStars',
            'inventory', 'reservationIdempotencyKey'
        ));
    }

    // Admin: show create form
    public function create()
    {
        $categories = Category::all();

        return view('admin.books.create', compact('categories'));
    }

    // Admin: save new book
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|min:5',
            'author' => 'required|string|max:255',
            'description' => 'required|string',
            'isbn' => ['required', 'string', 'max:20', 'unique:books,isbn'],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'stock' => ['prohibited'],
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'category_id' => 'required|exists:categories,id',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('books', 'public');
            $validated['image'] = 'storage/'.$path;
        }

        Book::create($validated);

        return redirect('/admin/books')
            ->with('success', 'Book added successfully.');
    }

    // Admin: show edit form
    public function edit(Book $book, InventoryReadService $inventoryReader)
    {
        $categories = Category::all();
        $inventory = $inventoryReader->forBook((int) $book->getKey());

        return view('admin.books.edit', compact('book', 'categories', 'inventory'));
    }

    // Admin: update book
    public function update(Request $request, Book $book)
    {
        $validated = $request->validate([
            'title' => 'required|string|min:5',
            'author' => 'required|string|max:255',
            'description' => 'required|string',
            'isbn' => ['required', 'string', 'max:20', Rule::unique('books', 'isbn')->ignore($book)],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'stock' => ['prohibited'],
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'category_id' => 'required|exists:categories,id',
        ]);

        if ($request->hasFile('image')) {

            // Delete the old uploaded image if it was stored by Laravel
            if (
                $book->image &&
                str_starts_with($book->image, 'storage/')
            ) {
                Storage::disk('public')->delete(
                    str_replace('storage/', '', $book->image)
                );
            }

            $path = $request->file('image')->store('books', 'public');

            $validated['image'] = 'storage/'.$path;
        } else {
            // Keep the existing image
            unset($validated['image']);
        }

        $book->update($validated);

        return redirect('/admin/books')
            ->with('success', 'Book updated successfully.');
    }

    // Admin: delete book
    public function destroy(Book $book)
    {
        $book->delete();

        return redirect('/admin/books')
            ->with('success', 'Book deleted successfully.');
    }

    // Admin: list books
    public function adminIndex(InventoryReadService $inventoryReader)
    {
        $books = Book::with('category')->get();
        $inventoryByBookId = $inventoryReader->forBooks($books);

        return view('admin.books.index', compact('books', 'inventoryByBookId'));
    }
}
