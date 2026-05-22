@extends('layouts.app')

@section('content')

<div class="books-grid">

@foreach ($books as $book)

    <div class="book-card">

        <img src="/{{ $book->image }}" class="book-image">
         <div class="book-content">

        <h2>
            <a href="/books/{{ $book->id }}">
                {{ $book->title }}
            </a>
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

@endforeach

</div>
@endsection