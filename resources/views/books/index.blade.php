@extends('layouts.app')

@section('content')

<div class="books-grid">

    @foreach ($books as $book)

        <a href="/books/{{ $book->id }}" class="book-link">

            <div class="book-card">

                <img src="/{{ $book->image }}" class="book-image">

                <div class="book-content">

                    <h2 class="book-title">
                        {{ $book->title }}
                    </h2>

                    <p>
                        <strong>Author:</strong>
                        {{ $book->author }}
                    </p>

                    <p>
                        {{ Str::limit($book->description, 100) }}
                    </p>

                </div>

            </div>

        </a>

    @endforeach

</div>

@endsection