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

            <section class="rounded-xl bg-gray-50 p-4">
                <h2 class="text-lg font-semibold text-gray-900">Reserve this book</h2>
                @if (session('reservation_error'))
                    <p role="alert" class="mt-2 rounded-lg bg-red-100 p-3 text-red-800">{{ session('reservation_error') }}</p>
                @endif
                @if ($inventory['state'] === 'available' || ((int) session('reservation_retry_book') === (int) $book->getKey() && (int) session('reservation_retry_user') === (int) auth()->id()))
                    @if ($inventory['state'] === 'available')
                        <p class="mt-2 text-gray-600">{{ $inventory['stock'] }} available</p>
                    @else
                        <p class="mt-2 text-gray-600">Retry the previous request to safely confirm whether it completed.</p>
                    @endif
                    @auth
                        <form action="{{ route('books.reservations.store', $book) }}" method="POST" class="mt-4">
                            @csrf
                            <input type="hidden" name="idempotency_key" value="{{ $reservationIdempotencyKey }}">
                            <button type="submit" class="rounded-lg bg-indigo-800 px-4 py-2 font-semibold text-gray-900 hover:bg-indigo-900 disabled:cursor-not-allowed disabled:bg-gray-700 disabled:text-gray-900 disabled:opacity-100">
                                {{ (int) session('reservation_retry_book') === (int) $book->getKey() && (int) session('reservation_retry_user') === (int) auth()->id() ? 'Retry reservation request' : 'Reserve one copy' }}
                            </button>
                        </form>
                    @else
                        <p class="mt-2 text-gray-600">Log in or register to reserve this book.</p>
                        <div class="mt-4 flex gap-3">
                            <a href="{{ route('login') }}" class="font-semibold text-indigo-700 hover:underline">Log in</a>
                            <a href="{{ route('register') }}" class="font-semibold text-indigo-700 hover:underline">Register</a>
                        </div>
                    @endauth
                @elseif ($inventory['state'] === 'out_of_stock')
                    <p class="mt-2 text-gray-600">This book is currently out of stock.</p>
                @elseif ($inventory['state'] === 'unknown')
                    <p class="mt-2 text-gray-600">Stock is unknown, so this book cannot currently be reserved.</p>
                @elseif ($inventory['state'] === 'missing')
                    <p class="mt-2 text-gray-600">Stock has not been configured for this book, so it cannot currently be reserved.</p>
                @else
                    <p role="status" class="mt-2 text-red-700">Availability could not be checked right now. Please try again later.</p>
                @endif
                @error('reservation')
                    <p class="mt-3 text-sm font-medium text-red-700">{{ $message }}</p>
                @enderror
            </section>
        </div>
    </div>
</article>

<section class="mx-auto mt-8 max-w-5xl rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 sm:p-8">
    <header class="mb-6">
        <h2 class="text-2xl font-bold tracking-tight text-gray-900">Reviews</h2>
    </header>

    @if (session('review_success'))
        <p class="mb-4 rounded-lg bg-green-100 p-3 text-green-800">{{ session('review_success') }}</p>
    @endif

    @if (session('review_error'))
        <p role="alert" class="mb-4 rounded-lg bg-red-100 p-3 text-red-800">{{ session('review_error') }}</p>
    @endif

    @if ($reviewCount === 0)
        <p class="text-gray-600">No reviews yet.</p>
    @else
        <div class="mb-6 flex flex-wrap items-center gap-2" aria-label="Average rating {{ number_format($averageRating, 1) }} out of 5 from {{ $reviewCount }} reviews">
            <span class="text-xl tracking-widest text-amber-500" aria-hidden="true">{{ str_repeat('★', $averageRatingStars).str_repeat('☆', 5 - $averageRatingStars) }}</span>
            <span class="font-semibold text-gray-900">{{ number_format($averageRating, 1) }}/5</span>
            <span class="text-gray-600">({{ $reviewCount }} {{ $reviewCount === 1 ? 'review' : 'reviews' }})</span>
        </div>

        <div class="flex flex-col gap-4">
            @foreach ($book->reviews as $review)
                <article class="rounded-xl bg-gray-50 p-4">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <p class="font-semibold text-gray-900">{{ $review->user->name }}</p>
                        <span class="text-lg tracking-widest text-amber-500" aria-label="{{ $review->score }} out of 5 stars">
                            {{ str_repeat('★', (int) $review->score).str_repeat('☆', 5 - (int) $review->score) }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ $review->created_at->format('F j, Y') }}</p>
                    <p class="mt-3 whitespace-pre-line leading-7 text-gray-700">{{ $review->body }}</p>
                </article>
            @endforeach
        </div>
    @endif

    @if ($canReview)
        <form action="{{ route('books.reviews.store', $book) }}" method="POST" class="mt-6 flex flex-col gap-4 border-t border-gray-200 pt-6">
            @csrf
            <h3 class="text-lg font-semibold text-gray-900">Write a review</h3>

            @error('book_id')
                <p class="rounded-lg bg-red-100 p-3 text-red-800">{{ $message }}</p>
            @enderror

            <fieldset class="flex flex-col gap-2">
                <legend class="text-sm font-medium text-gray-700">Choose a score from 1 to 5 stars</legend>
                <div x-data="{ rating: @js((int) old('score', 0)) }" class="flex items-center gap-1" role="radiogroup" aria-label="Rating from 1 to 5 stars">
                    @foreach (range(1, 5) as $score)
                        <input
                            id="score-{{ $score }}"
                            type="radio"
                            name="score"
                            value="{{ $score }}"
                            class="peer sr-only"
                            x-model.number="rating"
                            required
                            {{ (string) old('score') === (string) $score ? 'checked' : '' }}
                        >
                        <label
                            for="score-{{ $score }}"
                            class="inline-flex min-h-11 min-w-11 cursor-pointer items-center justify-center rounded-md text-3xl leading-none transition-colors peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-indigo-600"
                            :class="rating >= {{ $score }} ? 'text-amber-500' : 'text-gray-300'"
                            x-on:click="rating = {{ $score }}"
                            title="{{ $score }} {{ $score === 1 ? 'star' : 'stars' }}"
                        >
                            <span aria-hidden="true">★</span>
                            <span class="sr-only">{{ $score }} {{ $score === 1 ? 'star' : 'stars' }}</span>
                        </label>
                    @endforeach
                </div>
                @error('score')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </fieldset>

            <div class="flex flex-col gap-2">
                <label for="body" class="text-sm font-medium text-gray-700">Your review</label>
                <textarea id="body" name="body" rows="4" maxlength="5000" required class="rounded-lg border-gray-300 text-gray-900 focus:border-indigo-500 focus:ring-indigo-500">{{ old('body') }}</textarea>
                @error('body')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-fit rounded-lg bg-indigo-800 px-4 py-2 font-semibold text-gray-900 hover:bg-indigo-900">
                Submit review
            </button>
        </form>
    @elseif (auth()->check() && $hasReviewed)
        <p class="mt-6 border-t border-gray-200 pt-6 text-gray-600">You have already reviewed this book.</p>
    @elseif (auth()->check())
        @if (! $reviewEligibilityAvailable)
            <p role="status" class="mt-6 border-t border-gray-200 pt-6 text-red-700">Your purchase history could not be checked right now. Review eligibility is unavailable until the Inventory API responds.</p>
        @else
            <p class="mt-6 border-t border-gray-200 pt-6 text-gray-600">You can review this book after your purchase is completed.</p>
        @endif
    @else
        <p class="mt-6 border-t border-gray-200 pt-6 text-gray-600">
            Log in to write a review after purchasing this book.
            <a href="{{ route('login') }}" class="font-semibold text-indigo-700 hover:underline">Log in</a>
        </p>
    @endif
</section>

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
