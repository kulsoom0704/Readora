<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;

Route::get('/', function () {
    return view('home.index');
});

Route::get('/books', [BookController::class, 'index']);