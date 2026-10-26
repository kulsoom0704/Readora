@extends('layouts.app')

@section('content')

<main class="mx-auto max-w-5xl">
    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">My Reservations</h1>
        <p class="mt-2 text-gray-600">Books you have reserved through Readora.</p>
    </header>

    @if (session('success'))
        <p class="mb-4 rounded-lg bg-green-100 p-3 text-green-800">{{ session('success') }}</p>
    @endif

    @if (session('info'))
        <p class="mb-4 rounded-lg bg-gray-100 p-3 text-gray-800">{{ session('info') }}</p>
    @endif

    @if ($reservations->isEmpty())
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
            <p class="text-gray-700">You do not have any reservations yet.</p>
            <a href="/books" class="mt-4 inline-flex font-semibold text-indigo-700 hover:underline">Browse books</a>
        </section>
    @else
        <div class="flex flex-col gap-4">
            @foreach ($reservations as $reservation)
                <article class="flex flex-col gap-5 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 sm:flex-row sm:items-start">
                    <a href="{{ url('/books/'.$reservation->book_id) }}" class="shrink-0 self-center sm:self-start">
                        @if ($reservation->book->image)
                            <img
                                src="/{{ $reservation->book->image }}"
                                alt="{{ $reservation->book->title }} cover"
                                class="aspect-[2/3] w-28 rounded-lg object-cover shadow-sm ring-1 ring-black/5"
                            >
                        @else
                            <div class="flex aspect-[2/3] w-28 items-center justify-center rounded-lg bg-gray-100 p-3 text-center text-sm text-gray-500">
                                No cover available
                            </div>
                        @endif
                    </a>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">
                                    <a href="{{ url('/books/'.$reservation->book_id) }}" class="hover:text-indigo-800">
                                        {{ $reservation->book->title }}
                                    </a>
                                </h2>
                                <p class="mt-1 text-sm text-gray-600">Reservation #{{ $reservation->id }}</p>
                            </div>
                            @if ($reservation->status === 'cancelled')
                                <span class="w-fit rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700">
                                    Cancelled
                                </span>
                            @else
                                <span class="w-fit rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
                                    Reserved
                                </span>
                            @endif
                        </div>

                        <p class="mt-4 text-sm text-gray-600">
                            Reserved on {{ $reservation->created_at->format('F j, Y') }}
                        </p>
                        <a href="{{ route('reservations.confirmation', $reservation) }}" class="mt-3 inline-flex font-semibold text-indigo-700 hover:underline">
                            View confirmation
                        </a>
                        @if ($reservation->status === 'active')
                            <form action="{{ route('reservations.cancel', $reservation) }}" method="POST" class="mt-3">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg bg-red-700 px-4 py-2 font-semibold text-gray-900 hover:bg-red-800">
                                    Cancel Reservation
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</main>

@endsection
