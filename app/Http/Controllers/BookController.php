<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    // Public: show all books
    public function index()
    {
        $books = Book::all();

        return view('books.index', compact('books'));
    }
     public function show($id)
    {
        $book = Book::findOrFail($id);

        return view('books.show', compact('book'));
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
        'title' => 'required|string|max:255',
        'author' => 'required|string|max:255',
        'description' => 'required|string',
        'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'category_id' => 'required|exists:categories,id',
    ]);

    if ($request->hasFile('image')) {
        $path = $request->file('image')->store('books', 'public');
        $validated['image'] = 'storage/' . $path;
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
        'title' => 'required|string|max:255',
        'author' => 'required|string|max:255',
        'description' => 'required|string',
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

        $validated['image'] = 'storage/' . $path;
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