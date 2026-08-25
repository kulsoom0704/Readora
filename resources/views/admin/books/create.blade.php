@extends('layouts.app')

@section('content')

<div class="admin-book-form-page">

    <div class="admin-book-form-card">

        <h1>Add New Book</h1>
        <p class="form-subtitle">Add a new book to the Readora collection.</p>

        <form action="{{ route('admin.books.store') }}" method="POST" enctype="multipart/form-data">

            @csrf

            <div class="form-group">
                <label for="title">Title</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    placeholder="Enter book title"
                    required
                >
            </div>

            <div class="form-group">
                <label for="author">Author</label>
                <input
                    type="text"
                    id="author"
                    name="author"
                    placeholder="Enter author name"
                    required
                >
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea
                    id="description"
                    name="description"
                    placeholder="Enter book description"
                    required
                ></textarea>
            </div>

            <div class="form-group">
                <label for="image">Book Image</label>
                <input
                    type="file"
                    id="image"
                    name="image"
                    accept="image/*"
                >
                <small>Choose a JPG, PNG, or WEBP image.</small>
            </div>

            <div class="form-group">
                <label for="category_id">Category</label>

                <select id="category_id" name="category_id" required>

                    @foreach($categories as $category)

                        <option value="{{ $category->id }}">
                            {{ $category->name }}
                        </option>

                    @endforeach

                </select>
            </div>

            <button type="submit" class="save-book-button">
                Add Book
            </button>

        </form>

    </div>

</div>

@endsection