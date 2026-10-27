<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Review;
use App\Services\InventoryApiException;
use App\Services\InventoryReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    public function store(Request $request, Book $book, InventoryReservationService $reservationReader): RedirectResponse
    {
        $user = $request->user();

        try {
            $hasPurchasedBook = $reservationReader->userHasPurchasedBook($user, (int) $book->getKey());
        } catch (InventoryApiException) {
            return redirect('/books/'.$book->getKey())
                ->with('review_error', 'Purchase history could not be checked. Please try submitting your review later.');
        }

        abort_unless($hasPurchasedBook, 403);

        $validated = Validator::make(
            [
                'score' => $request->input('score'),
                'body' => $request->input('body'),
                'book_id' => $book->getKey(),
            ],
            [
                'score' => ['required', 'integer', 'between:1,5'],
                'body' => ['required', 'string', 'max:5000'],
                'book_id' => [
                    'required',
                    Rule::unique('reviews', 'book_id')->where('user_id', $user->getKey()),
                ],
            ]
        )->validate();

        Review::create([
            'user_id' => $user->getKey(),
            'book_id' => $book->getKey(),
            'score' => $validated['score'],
            'body' => $validated['body'],
        ]);

        return redirect('/books/'.$book->getKey())
            ->with('review_success', 'Thank you for sharing your review.');
    }
}
