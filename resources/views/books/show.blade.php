@extends('layouts.app')

@section('content')

<div class="card">

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

@endsection