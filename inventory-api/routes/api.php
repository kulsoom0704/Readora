<?php

use App\Http\Controllers\AdminReservationController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('service.token')->group(function () {
    Route::get('/health', function () {
        return response()->json(['status' => 'ok']);
    })->name('health');

    Route::get('/inventory/books/{bookId}', [InventoryController::class, 'show'])
        ->whereNumber('bookId')
        ->name('inventory.show');

    Route::put('/inventory/books/{bookId}', [InventoryController::class, 'update'])
        ->middleware(['trusted.identity', 'trusted.admin'])
        ->whereNumber('bookId')
        ->name('inventory.update');

    Route::middleware('trusted.identity')->group(function () {
        Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
        Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
        Route::get('/reservations/{reservationId}', [ReservationController::class, 'show'])
            ->whereNumber('reservationId')
            ->name('reservations.show');
        Route::delete('/reservations/{reservationId}', [ReservationController::class, 'destroy'])
            ->whereNumber('reservationId')
            ->name('reservations.destroy');
    });

    Route::middleware(['trusted.identity', 'trusted.admin'])->prefix('admin')->group(function () {
        Route::get('/reservations', [AdminReservationController::class, 'index'])->name('admin.reservations.index');
        Route::patch('/reservations/{reservationId}/purchase', [AdminReservationController::class, 'purchase'])
            ->whereNumber('reservationId')
            ->name('admin.reservations.purchase');
    });
});
