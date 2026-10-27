<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateReservationRequest;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReservationController extends Controller
{
    public function index(Request $request): Response
    {
        $page = Reservation::where('user_id', $this->userId($request))
            ->latest('id')
            ->paginate(50);

        return response()->json([
            'data' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function store(CreateReservationRequest $request, ReservationService $reservations): Response
    {
        $validated = $request->validated();
        $result = $reservations->reserve(
            $this->userId($request),
            (int) $validated['book_id'],
            $validated['idempotency_key'],
        );

        if ($result['conflict']) {
            return response()->json([
                'message' => 'The requested book is unavailable or the idempotency key conflicts with another request.',
            ], Response::HTTP_CONFLICT);
        }

        return response()->json(
            ['data' => $result['reservation']],
            $result['created'] ? Response::HTTP_CREATED : Response::HTTP_OK,
        );
    }

    public function show(Request $request, string $reservationId): Response
    {
        $reservation = Reservation::findOrFail($reservationId);

        if ((int) $reservation->user_id !== $this->userId($request)) {
            return response()->json(['message' => 'You cannot access this reservation.'], Response::HTTP_FORBIDDEN);
        }

        return response()->json(['data' => $reservation]);
    }

    public function destroy(Request $request, string $reservationId, ReservationService $reservations): Response
    {
        $reservation = Reservation::findOrFail($reservationId);

        if ((int) $reservation->user_id !== $this->userId($request)) {
            return response()->json(['message' => 'You cannot cancel this reservation.'], Response::HTTP_FORBIDDEN);
        }

        $result = $reservations->cancel($this->userId($request), (int) $reservationId);

        if ($result['status'] !== 'cancelled') {
            $message = $result['status'] === 'unknown_stock'
                ? 'Stock is unknown, so this reservation cannot safely restore a copy.'
                : 'Only an active reservation can be cancelled.';

            return response()->json(['message' => $message], Response::HTTP_CONFLICT);
        }

        return response()->json(['data' => $result['reservation']]);
    }

    private function userId(Request $request): int
    {
        return (int) $request->attributes->get('readora_user_id');
    }
}
