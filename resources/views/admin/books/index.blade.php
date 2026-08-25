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
                        <strong>Category:</strong>
                        <span class="book-category">
                            {{ $book->category->name }}
                        </span>
                    </p>

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