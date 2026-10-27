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
                    value="{{ old('title', $book->title) }}"

                    required
                    {{-- @error('title') is-invalid @enderror --}}
                >
                @error('title')
                    <span class="error-message">{{ $message }}</span>
                @enderror
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
                <label for="isbn">ISBN</label>
                <input
                    type="text"
                    id="isbn"
                    name="isbn"
                    value="{{ old('isbn', $book->isbn) }}"
                    maxlength="20"
                    required
                >
                @error('isbn')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="price">Price (€)</label>
                <input
                    type="number"
                    id="price"
                    name="price"
                    value="{{ old('price', $book->price) }}"
                    min="0"
                    step="0.01"
                    required
                >
                @error('price')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label>Stock (Inventory API)</label>
                @if ($inventory['state'] === 'available')
                    <p>{{ $inventory['stock'] }} available</p>
                @elseif ($inventory['state'] === 'out_of_stock')
                    <p>0 — out of stock</p>
                @elseif ($inventory['state'] === 'unknown')
                    <p>Unknown</p>
                @elseif ($inventory['state'] === 'missing')
                    <p>Not configured</p>
                @else
                    <p class="error-message">Availability could not be checked right now.</p>
                @endif
                <small>Stock is read-only in this phase and cannot be changed from this form.</small>
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
