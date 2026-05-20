@extends('layouts.app')

@section('content')

<h1>Categories</h1>

@foreach ($categories as $category)

    <div class="card">

        <h2>
            <a href="/categories/{{ $category->id }}">
                {{ $category->name }}
            </a>
        </h2>

    </div>

@endforeach

@endsection