<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    public function store(Request $request, Book $book): RedirectResponse
    {
        $user = $request->user();

        $hasPurchasedBook = $user->reservations()
            ->where('book_id', $book->getKey())
            ->where('status', 'purchased')
            ->exists();

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
