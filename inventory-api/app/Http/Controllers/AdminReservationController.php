<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\ReservationService;
use Symfony\Component\HttpFoundation\Response;

class AdminReservationController extends Controller
{
    public function index(): Response
    {
        $page = Reservation::latest('id')->paginate(50);

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

    public function purchase(string $reservationId, ReservationService $reservations): Response
    {
        $result = $reservations->purchase((int) $reservationId);

        if ($result['status'] !== 'purchased') {
            $reservation = Reservation::find($reservationId);

            if ($reservation === null) {
                return response()->json(['message' => 'Reservation not found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'message' => 'Only an active reservation can be marked as purchased.',
            ], Response::HTTP_CONFLICT);
        }

        return response()->json(['data' => $result['reservation']]);
    }
}
