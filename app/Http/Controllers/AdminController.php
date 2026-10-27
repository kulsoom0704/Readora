<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\InventoryApiException;
use App\Services\InventoryReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index()
    {
        $users = User::all();

        return view('admin.users', compact('users'));
    }

    public function makeAdmin($id)
    {
        $user = User::findOrFail($id);

        $user->is_admin = true;

        $user->save();

        return redirect('/admin/users');
    }

    public function removeAdmin($id)
    {
        $user = User::findOrFail($id);

        $user->is_admin = false;

        $user->save();

        return redirect('/admin/users');
    }

    public function reservationsIndex(Request $request, InventoryReservationService $reservationService): View
    {
        $reservationError = null;

        try {
            $reservations = $reservationService->forAdmin($request->user());
        } catch (InventoryApiException) {
            $reservations = collect();
            $reservationError = 'Reservations could not be loaded from the Inventory API. Please try again later.';
        }

        return view('admin.reservations.index', compact('reservations', 'reservationError'));
    }

    public function markReservationAsPurchased(
        Request $request,
        int $reservationId,
        InventoryReservationService $reservationService,
    ): RedirectResponse {
        try {
            $reservationService->purchase($request->user(), $reservationId);
        } catch (InventoryApiException $exception) {
            if ($exception->statusCode === 409) {
                return redirect()->route('admin.reservations.index')
                    ->with('info', 'Only active reservations can be marked as purchased.');
            }

            if ($exception->statusCode === 404) {
                return redirect()->route('admin.reservations.index')
                    ->with('info', 'That reservation was not found in the Inventory API.');
            }

            return redirect()->route('admin.reservations.index')
                ->with('error', 'The Inventory API did not confirm the purchase update. Please try again later.');
        }

        return redirect()->route('admin.reservations.index')
            ->with('success', 'Reservation marked as purchased. Stock was not changed.');
    }
}
