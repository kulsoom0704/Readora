<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
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

    public function reservationsIndex(): View
    {
        $reservations = Reservation::query()
            ->with(['user', 'book'])
            ->latest()
            ->get();

        return view('admin.reservations.index', compact('reservations'));
    }

    public function markReservationAsPurchased(Reservation $reservation): RedirectResponse
    {
        $updatedReservations = Reservation::query()
            ->whereKey($reservation->getKey())
            ->where('status', 'active')
            ->update([
                'status' => 'purchased',
                'updated_at' => now(),
            ]);

        if ($updatedReservations === 1) {
            return redirect()->route('admin.reservations.index')
                ->with('success', 'Reservation marked as purchased. Stock was not changed.');
        }

        return redirect()->route('admin.reservations.index')
            ->with('info', 'Only active reservations can be marked as purchased.');
    }
}
