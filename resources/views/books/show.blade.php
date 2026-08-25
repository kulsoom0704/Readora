@extends('layouts.app')

@section('content')

<div class="book-details-card">

    <div class="book-details-image">
        <img src="/{{ $book->image }}" alt="{{ $book->title }}">
    </div>

    <div class="book-details-content">

        <h1>{{ $book->title }}</h1>

        <p>
            <strong>Author:</strong>
            {{ $book->author }}
        </p>

        <p>
            <strong>Description:</strong>
            {{ $book->description }}
        </p>

        <p>
            <strong>Category:</strong>
            <span class="book-category">
                {{ $book->category->name }}
            </span>
        </p>

    </div>

</div>

@endsection

{{-- @extends('layouts.app')

@section('content')

<div class="card">
    <img src="/{{ $book->image }}" class="book-image">

    <h1>{{ $book->title }}</h1>

    <p>
        <strong>Author:</strong>
        {{ $book->author }}
    </p>

    <p>
        <strong>Description:</strong>
        {{ $book->description }}
    </p>

    <p>
        <strong>Category:</strong>
        {{ $book->category->name }}
    </p>

</div>

@endsection --}}