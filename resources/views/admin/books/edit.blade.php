@extends('layouts.app')

@section('content')

<div class="admin-book-form-page">

    <div class="admin-book-form-card">

        <h1>Edit Book</h1>
        <p class="form-subtitle">Update the information for this book.</p>

        <form
            action="{{ route('admin.books.update', $book->id) }}"
            method="POST"
            enctype="multipart/form-data"
        >

            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="title">Title</label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    value="{{ $book->title }}"
                    required
                >
            </div>

            <div class="form-group">
                <label for="author">Author</label>

                <input
                    type="text"
                    id="author"
                    name="author"
                    value="{{ $book->author }}"
                    required
                >
            </div>

            <div class="form-group">
                <label for="description">Description</label>

                <textarea
                    id="description"
                    name="description"
                    required
                >{{ $book->description }}</textarea>
            </div>

            <div class="form-group">
                <label for="image">Book Image</label>

                <input
                    type="file"
                    id="image"
                    name="image"
                    accept="image/*"
                >

                <small>
                    Choose a new image only if you want to replace the current one.
                </small>
            </div>

            <div class="current-image">

                <p>Current image:</p>

                @if($book->image)
                    <img
                        src="/{{ $book->image }}"
                        alt="{{ $book->title }}"
                    >
                @endif

            </div>

            <div class="form-group">
                <label for="category_id">Category</label>

                <select id="category_id" name="category_id" required>

                    @foreach($categories as $category)

                        <option
                            value="{{ $category->id }}"
                            {{ $book->category_id == $category->id ? 'selected' : '' }}
                        >
                            {{ $category->name }}
                        </option>

                    @endforeach

                </select>
            </div>

            <button type="submit" class="save-book-button">
                Update Book
            </button>

        </form>

    </div>

</div>

@endsection