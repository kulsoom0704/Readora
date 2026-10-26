<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $reservations = $request->user()
            ->reservations()
            ->with('book')
            ->latest()
            ->get();

        return view('reservations.index', compact('reservations'));
    }

    public function store(Request $request, Book $book): RedirectResponse
    {
        $reservation = DB::transaction(function () use ($request, $book): Reservation {
            $updatedBooks = Book::query()
                ->whereKey($book->getKey())
                ->where('stock', '>=', 1)
                ->decrement('stock');

            if ($updatedBooks !== 1) {
                throw ValidationException::withMessages([
                    'reservation' => 'This book is not currently available.',
                ]);
            }

            return $request->user()->reservations()->create([
                'book_id' => $book->getKey(),
            ]);
        });

        return redirect()->route('reservations.confirmation', $reservation);
    }

    public function show(Request $request, Reservation $reservation): View
    {
        abort_unless(
            (int) $reservation->user_id === (int) $request->user()->getAuthIdentifier(),
            403
        );

        $reservation->load('book');

        return view('reservations.confirmation', compact('reservation'));
    }

    public function cancel(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless(
            (int) $reservation->user_id === (int) $request->user()->getAuthIdentifier(),
            403
        );

        $cancelled = DB::transaction(function () use ($reservation): bool {
            $updatedReservations = Reservation::query()
                ->whereKey($reservation->getKey())
                ->where('status', 'active')
                ->update([
                    'status' => 'cancelled',
                    'updated_at' => now(),
                ]);

            if ($updatedReservations !== 1) {
                return false;
            }

            $restoredBooks = Book::query()
                ->whereKey($reservation->book_id)
                ->increment('stock');

            if ($restoredBooks !== 1) {
                throw (new ModelNotFoundException)->setModel(Book::class, [$reservation->book_id]);
            }

            return true;
        });

        if ($cancelled) {
            return redirect()->route('reservations.index')
                ->with('success', 'Reservation cancelled. One copy was returned to stock.');
        }

        return redirect()->route('reservations.index')
            ->with('info', 'This reservation is already cancelled. No stock was changed.');
    }
}
