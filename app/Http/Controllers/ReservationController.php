<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\InventoryApiException;
use App\Services\InventoryReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(Request $request, InventoryReservationService $reservations): View
    {
        $reservationError = null;

        try {
            $reservationRecords = $reservations->forUser($request->user());
        } catch (InventoryApiException) {
            $reservationRecords = collect();
            $reservationError = 'Your reservations could not be loaded right now. Please try again later.';
        }

        return view('reservations.index', [
            'reservations' => $reservationRecords,
            'reservationError' => $reservationError,
        ]);
    }

    public function store(Request $request, Book $book, InventoryReservationService $reservations): RedirectResponse
    {
        $user = $request->user();
        $idempotencySessionKey = 'inventory_reservation_keys.'.$user->getAuthIdentifier().'.'.$book->getKey();
        $idempotencyKey = $request->input('idempotency_key');

        if ($idempotencyKey !== null) {
            $request->validate([
                'idempotency_key' => ['required', 'uuid'],
            ]);
        } else {
            $idempotencyKey = $request->session()->get($idempotencySessionKey);
        }

        if (! is_string($idempotencyKey) || $idempotencyKey === '') {
            $idempotencyKey = (string) Str::uuid();
        }

        $request->session()->put($idempotencySessionKey, $idempotencyKey);

        try {
            $reservation = $reservations->reserve($user, $book, $idempotencyKey);
        } catch (InventoryApiException $exception) {
            if ($exception->statusCode === 409) {
                $request->session()->forget($idempotencySessionKey);
                $request->session()->forget('reservation_retry_book');
                $request->session()->forget('reservation_retry_user');

                return redirect('/books/'.$book->getKey())
                    ->with('reservation_error', 'This book is unavailable or the request conflicted. No reservation was confirmed.');
            }

            $request->session()->put('reservation_retry_book', (int) $book->getKey());
            $request->session()->put('reservation_retry_user', (int) $user->getAuthIdentifier());

            return redirect('/books/'.$book->getKey())
                ->with('reservation_error', 'The Inventory API did not confirm this reservation. Please retry; the same request key will be reused safely.');
        }

        $request->session()->forget($idempotencySessionKey);
        $request->session()->forget('reservation_retry_book');
        $request->session()->forget('reservation_retry_user');

        return redirect()->route('reservations.confirmation', $reservation->id);
    }

    public function show(Request $request, int $reservationId, InventoryReservationService $reservations): View|RedirectResponse
    {
        try {
            $reservation = $reservations->findForUser($request->user(), $reservationId);
        } catch (InventoryApiException $exception) {
            if ($exception->statusCode === 403) {
                abort(403);
            }

            if ($exception->statusCode === 404) {
                abort(404);
            }

            return redirect()->route('reservations.index')
                ->with('error', 'Reservation details could not be loaded right now.');
        }

        return view('reservations.confirmation', compact('reservation'));
    }

    public function cancel(Request $request, int $reservationId, InventoryReservationService $reservations): RedirectResponse
    {
        try {
            $cancellation = $reservations->cancelForUser($request->user(), $reservationId);
        } catch (InventoryApiException $exception) {
            if ($exception->statusCode === 403) {
                abort(403);
            }

            if ($exception->statusCode === 404) {
                abort(404);
            }

            if ($exception->statusCode === 409) {
                return redirect()->route('reservations.index')
                    ->with('info', 'Only active reservations with known stock can be cancelled. No cancellation was confirmed.');
            }

            return redirect()->route('reservations.index')
                ->with('error', 'The Inventory API did not confirm cancellation. Please check the reservation status before retrying.');
        }

        if (! $cancellation['newly_cancelled']) {
            return redirect()->route('reservations.index')
                ->with('info', 'This reservation was already cancelled. No additional stock was restored.');
        }

        return redirect()->route('reservations.index')
            ->with('success', 'Reservation cancelled. The Inventory API restored one copy to stock.');
    }
}
