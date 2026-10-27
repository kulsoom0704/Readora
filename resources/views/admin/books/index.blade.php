@extends('layouts.app')

@section('content')

<div class="admin-books-page">

    <div class="admin-books-header">
        <div>
            <h1>Manage Books</h1>
            <p>Add, edit, or remove books from Readora.</p>
        </div>

        <a href="{{ route('admin.books.create') }}" class="add-book-button">
            + Add New Book
        </a>
    </div>

    @if(session('success'))
        <div class="success-message">
            {{ session('success') }}
        </div>
    @endif

    <div class="admin-books-list">

        @foreach($books as $book)

            <div class="admin-book-card">

                <div class="admin-book-info">

                    <h2>{{ $book->title }}</h2>

                    <p>
                        <strong>Author:</strong>
                        {{ $book->author }}
                    </p>

                    <p>
                        <strong>ISBN:</strong>
                        {{ $book->isbn ?: 'Not set' }}
                    </p>

                    <p>
                        <strong>Category:</strong>
                        <span class="book-category">
                            {{ $book->category->name }}
                        </span>
                    </p>

                    @php($inventory = $inventoryByBookId[$book->id])
                    <p>
                        <strong>Stock (Inventory API):</strong>
                        @if ($inventory['state'] === 'available')
                            {{ $inventory['stock'] }}
                        @elseif ($inventory['state'] === 'out_of_stock')
                            0
                        @elseif ($inventory['state'] === 'unknown')
                            Unknown
                        @elseif ($inventory['state'] === 'missing')
                            Not configured
                        @else
                            Unavailable right now
                        @endif
                    </p>

                    <form action="{{ route('admin.inventory.update', $book) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="stock-{{ $book->id }}">Set stock quantity</label>
                            <input
                                type="number"
                                id="stock-{{ $book->id }}"
                                name="stock[{{ $book->id }}]"
                                value="{{ old('stock.'.$book->id, $inventory['stock']) }}"
                                min="0"
                                step="1"
                                required
                            >
                            @error('stock.'.$book->id)
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                            <button type="submit" class="save-book-button">Update stock</button>
                        </div>
                    </form>

                    <p class="book-description">
                        {{ $book->description }}
                    </p>

                </div>

                <div class="admin-book-actions">

                    <a href="{{ route('admin.books.edit', $book->id) }}"
                       class="edit-button">
                        Edit
                    </a>

                    <form action="{{ route('admin.books.destroy', $book->id) }}" method="POST">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                                class="delete-button"
                                onclick="return confirm('Are you sure you want to delete this book?')">
                            Delete
                        </button>
                    </form>

                </div>

            </div>

        @endforeach

    </div>

</div>

@endsection
