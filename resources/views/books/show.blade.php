@extends('layouts.app')

@section('content')

<article class="mx-auto max-w-5xl rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 sm:p-8">
    <div class="grid grid-cols-1 gap-6 md:grid-cols-[280px_minmax(0,1fr)] md:items-start md:gap-8">
        <div class="flex w-full justify-center md:justify-start">
            <img
                src="/{{ $book->image }}"
                alt="{{ $book->title }}"
                class="aspect-[2/3] w-full max-w-[220px] rounded-xl object-cover shadow-md ring-1 ring-black/5 md:max-w-[280px]"
            >
        </div>

        <div class="flex min-w-0 flex-col gap-4 text-left">
            <header class="flex flex-col gap-2">
                <span class="w-fit rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700">
                    Category:
                    {{ $book->category->name }}
                </span>
                <h1 class="text-3xl font-bold leading-tight tracking-tight text-gray-900 sm:text-4xl">
                    {{ $book->title }}
                </h1>
                <p class="text-lg text-gray-600">
                    By {{ $book->author }}
                </p>
            </header>

            <dl class="grid grid-cols-1 gap-4 rounded-xl bg-gray-50 p-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <dt class="text-sm font-medium uppercase tracking-wide text-gray-500">Price</dt>
                    <dd class="text-2xl font-semibold text-gray-900">
                        @if ($book->price !== null)
                            €{{ number_format((float) $book->price, 2) }}
                        @else
                            Not set
                        @endif
                    </dd>
                </div>
                <div class="flex flex-col gap-2">
                    <dt class="text-sm font-medium uppercase tracking-wide text-gray-500">ISBN</dt>
                    <dd class="break-all text-gray-900">{{ $book->isbn ?? 'Not set' }}</dd>
                </div>
            </dl>

            <section class="flex flex-col gap-2">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Description</h2>
                <p class="whitespace-pre-line leading-7 text-gray-700">{{ $book->description }}</p>
            </section>
        </div>
    </div>
</article>

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
