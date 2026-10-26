@extends('layouts.app')

@section('content')

<main class="mx-auto max-w-5xl">
    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Manage Reservations</h1>
        <p class="mt-2 text-gray-600">Review reservations and record completed purchases.</p>
    </header>

    @if (session('success'))
        <p class="mb-4 rounded-lg bg-green-100 p-3 text-green-800">{{ session('success') }}</p>
    @endif

    @if (session('info'))
        <p class="mb-4 rounded-lg bg-gray-100 p-3 text-gray-800">{{ session('info') }}</p>
    @endif

    @if ($reservations->isEmpty())
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <p class="text-gray-700">There are no reservations yet.</p>
        </section>
    @else
        <div class="flex flex-col gap-4">
            @foreach ($reservations as $reservation)
                <article class="flex flex-col gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $reservation->book->title }}</h2>
                        <p class="mt-1 text-sm text-gray-600">Customer: {{ $reservation->user->name }} ({{ $reservation->user->email }})</p>
                        <p class="mt-1 text-sm text-gray-600">Reservation #{{ $reservation->id }} · {{ $reservation->created_at->format('F j, Y') }}</p>
                    </div>

                    <div class="flex flex-col items-start gap-3 sm:items-end">
                        @if ($reservation->status === 'purchased')
                            <span class="rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">Purchased</span>
                        @elseif ($reservation->status === 'cancelled')
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700">Cancelled</span>
                        @else
                            <span class="rounded-full bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-800">Active</span>
                            <form action="{{ route('admin.reservations.purchase', $reservation) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-lg bg-indigo-800 px-4 py-2 font-semibold text-gray-900 hover:bg-indigo-900">
                                    Mark as purchased
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
