@extends('layouts.app')

@section('content')

<div class="card">

    <h1>{{ $category->name }}</h1>

    <h3>Books in this category:</h3>

    @foreach ($category->books as $book)

        <p>
            <a href="/books/{{ $book->id }}">
                {{ $book->title }}
            </a>
        </p>

    @endforeach

</div>

@endsection