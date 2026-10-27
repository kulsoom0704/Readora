@extends('layouts.app')

@section('content')

<main class="mx-auto max-w-2xl rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
    <p class="text-sm font-semibold uppercase tracking-wide {{ $reservation->status === 'cancelled' ? 'text-gray-600' : 'text-green-700' }}">
        @if ($reservation->status === 'cancelled')
            Reservation cancelled
        @elseif ($reservation->status === 'purchased')
            Reservation purchased
        @else
            Reservation confirmed
        @endif
    </p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900">
        {{ $reservation->book?->title ?? 'Book no longer in catalogue' }}
    </h1>
    <p class="mt-3 text-gray-600">
        @if ($reservation->status === 'cancelled')
            This reservation was cancelled. Reservation reference: #{{ $reservation->id }}.
        @elseif ($reservation->status === 'purchased')
            This reservation has been marked as purchased. Reservation reference: #{{ $reservation->id }}.
        @else
            One copy has been reserved for you. Reservation reference: #{{ $reservation->id }}.
        @endif
    </p>

    @if ($reservation->status === 'active')
        <section class="mt-6 rounded-xl bg-gray-50 p-4">
            <h2 class="text-lg font-semibold text-gray-900">Pickup and payment</h2>
            <p class="mt-2 text-gray-700">Contact Readora to arrange pickup and payment.</p>
        </section>
    @endif

    @if ($reservation->book)
        <a href="{{ url('/books/'.$reservation->book_id) }}" class="mt-6 inline-flex font-semibold text-indigo-700 hover:underline">
            Back to book details
        </a>
    @endif
</main>

@endsection
