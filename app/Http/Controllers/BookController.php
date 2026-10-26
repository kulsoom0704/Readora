<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    // Public: show all books
    public function index()
    {
        $books = Book::all();

        return view('books.index', compact('books'));
    }

    public function show(Request $request, $id)
    {
        $book = Book::query()
            ->with(['category', 'reviews.user'])
            ->findOrFail($id);

        $reviewCount = $book->reviews->count();
        $averageRating = $book->reviews->avg('score');
        $averageRatingStars = $averageRating === null ? 0 : (int) round($averageRating);

        $hasReviewed = false;
        $canReview = false;

        if ($request->user()) {
            $hasReviewed = $request->user()->reviews()
                ->where('book_id', $book->getKey())
                ->exists();

            $canReview = ! $hasReviewed && $request->user()->reservations()
                ->where('book_id', $book->getKey())
                ->where('status', 'purchased')
                ->exists();
        }

        return view('books.show', compact(
            'book',
            'canReview',
            'hasReviewed',
            'reviewCount',
            'averageRating',
            'averageRatingStars'
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
            'stock' => ['required', 'integer', 'min:0'],
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
    public function edit(Book $book)
    {
        $categories = Category::all();

        return view('admin.books.edit', compact('book', 'categories'));
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
            'stock' => ['nullable', 'integer', 'min:0'],
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
    public function adminIndex()
    {
        $books = Book::with('category')->get();

        return view('admin.books.index', compact('books'));
    }
}
