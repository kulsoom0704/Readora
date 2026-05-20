@extends('layouts.app')

@section('content')

<h1>Books</h1>

@foreach ($books as $book)

    <div class="card">
        <h2>{{ $book->title }}</h2>

        <p>
            <strong>Author:</strong>
            {{ $book->author }}
        </p>

        <p>
            {{ $book->description }}
        </p>

        <p>
            <strong>Category:</strong>
            {{ $book->category->name }}
        </p>
    </div>

@endforeach

@endsection