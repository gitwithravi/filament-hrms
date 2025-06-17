<?php

use App\Models\User;
use App\Models\Book;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// Test routes for HasUuid trait
Route::get('/test/user/{user}', function (User $user) {
    return response()->json([
        'message' => 'User found by UUID',
        'user' => [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
        ]
    ]);
});

Route::get('/test/book/{book}', function (Book $book) {
    return response()->json([
        'message' => 'Book found by UUID',
        'book' => [
            'id' => $book->id,
            'uuid' => $book->uuid,
            'title' => $book->title,
            'author' => $book->author,
        ]
    ]);
});
