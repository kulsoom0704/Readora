<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContactController;

Route::get('/', function () {
    return view('home.index');
});

Route::get('/books', [BookController::class, 'index']);

Route::get('/books/{id}', [BookController::class, 'show']);

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