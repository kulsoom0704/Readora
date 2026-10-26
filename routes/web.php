<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home.index');
});

Route::get('/books', [BookController::class, 'index']);

Route::get('/books/{id}', [BookController::class, 'show']);

Route::middleware('auth')->group(function () {
    Route::get('/my-reservations', [ReservationController::class, 'index'])
        ->name('reservations.index');

    Route::delete('/my-reservations/{reservation}', [ReservationController::class, 'cancel'])
        ->name('reservations.cancel');

    Route::post('/books/{book}/reservations', [ReservationController::class, 'store'])
        ->name('books.reservations.store');

    Route::get('/reservations/{reservation}/confirmation', [ReservationController::class, 'show'])
        ->name('reservations.confirmation');
});

Route::get('/categories', [CategoryController::class, 'index']);

Route::get('/categories/{id}', [CategoryController::class, 'show']);

Route::view('/faq', 'faq');

Route::get('/contact', [ContactController::class, 'index']);

Route::post('/contact', [ContactController::class, 'send']);

Route::middleware(['admin'])->group(function () {

    Route::get('/admin/users', [AdminController::class, 'index']);

    Route::get('/admin/make-admin/{id}', [AdminController::class, 'makeAdmin']);

    Route::get('/admin/remove-admin/{id}', [AdminController::class, 'removeAdmin']);

    Route::get('/admin/books', [BookController::class, 'adminIndex'])
        ->name('admin.books.index');

    Route::get('/admin/books/create', [BookController::class, 'create'])
        ->name('admin.books.create');

    Route::post('/admin/books', [BookController::class, 'store'])
        ->name('admin.books.store');

    Route::get('/admin/books/{book}/edit', [BookController::class, 'edit'])
        ->name('admin.books.edit');

    Route::put('/admin/books/{book}', [BookController::class, 'update'])
        ->name('admin.books.update');

    Route::delete('/admin/books/{book}', [BookController::class, 'destroy'])
        ->name('admin.books.destroy');
});
require __DIR__.'/auth.php';
