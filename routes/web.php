<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\SignupController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\FavoriteSongController;
use App\Http\Controllers\MusicDiscoveryController;
use App\Http\Controllers\VisualBoardController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\ProfileController;

Route::get('/', [QuoteController::class, 'index'])->name('home');
Route::get('/users/{user}/avatar', [ProfileController::class, 'image'])->name('users.avatar');
Route::get('/soundtrack/discover', [FavoriteSongController::class, 'discover'])->name('soundtrack.discover');

Route::get('/signup', [function () {
    return view('Authentication.SignUp', ['page'=>'Sign Up']);
}]);
Route::post('/signup', [SignupController::class, 'signup']);

Route::get('/login', function () {
    return view('Authentication.LogIn', ['page'=>'Log In']);
})->name('login');
Route::post('/login', [LoginController::class, 'login']);

Route::get('/logout', [LogoutController::class, 'logout']);

Route::post('/', [QuoteController::class, 'store']);

Route::put('/quote/{quote}', [QuoteController::class, 'update'])->name('edit_quote');
Route::delete('/quote/{quote}', [QuoteController::class, 'destroy'])->name('delete_quote');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'avatar'])->middleware('throttle:5,1')->name('profile.avatar');
    Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
    Route::patch('/profile/password', [ProfileController::class, 'password'])->name('profile.password');

    Route::post('/quotes/{quote}/like', [SocialController::class, 'quoteLike'])->middleware('throttle:30,1')->name('quotes.like');
    Route::post('/quotes/{quote}/comments', [SocialController::class, 'quoteComment'])->middleware('throttle:12,1')->name('quotes.comments.store');
    Route::delete('/quote-comments/{comment}', [SocialController::class, 'deleteQuoteComment'])->name('quotes.comments.destroy');
    Route::post('/boards/{visualBoard}/like', [SocialController::class, 'boardLike'])->middleware('throttle:30,1')->name('boards.like');
    Route::post('/boards/{visualBoard}/comments', [SocialController::class, 'boardComment'])->middleware('throttle:12,1')->name('boards.comments.store');
    Route::delete('/board-comments/{comment}', [SocialController::class, 'deleteBoardComment'])->name('boards.comments.destroy');

    Route::post('/code-exchange/{codeSnippet}/like', [SocialController::class, 'codeLike'])->middleware('throttle:30,1')->name('code-exchange.like');
    Route::post('/code-exchange/{codeSnippet}/comments', [SocialController::class, 'codeComment'])->middleware('throttle:12,1')->name('code-exchange.comments.store');
    Route::delete('/code-comments/{comment}', [SocialController::class, 'deleteCodeComment'])->name('code-exchange.comments.destroy');

    Route::post('/soundtrack/public/{favoriteSong}/like', [SocialController::class, 'songLike'])->middleware('throttle:30,1')->name('soundtrack.public.like');
    Route::post('/soundtrack/public/{favoriteSong}/comments', [SocialController::class, 'songComment'])->middleware('throttle:12,1')->name('soundtrack.public.comments.store');
    Route::delete('/song-comments/{comment}', [SocialController::class, 'deleteSongComment'])->name('soundtrack.public.comments.destroy');

    Route::get('/soundtrack', [FavoriteSongController::class, 'index'])->name('soundtrack.index');

    Route::post('/soundtrack', [FavoriteSongController::class, 'store'])->name('soundtrack.store');

    Route::put('/soundtrack/{favoriteSong}', [FavoriteSongController::class, 'update'])->name('soundtrack.update');

    Route::delete('/soundtrack/{favoriteSong}', [FavoriteSongController::class, 'destroy'])->name('soundtrack.destroy');

    Route::patch('/soundtrack/{favoriteSong}/repeat', [FavoriteSongController::class, 'repeat'])->name('soundtrack.repeat');

    Route::get('/soundtrack/spotify-metadata', \App\Http\Controllers\SpotifyMetadataController::class)
        ->middleware('throttle:12,1')->name('soundtrack.spotify-metadata');

    Route::get('/soundtrack/youtube-search', [MusicDiscoveryController::class, 'youtube'])
    ->middleware('throttle:20,1')
    ->name('soundtrack.youtube-search');
});

// Visual Boards — anyone can explore; signed-in users manage only their own work.
Route::get('/boards', [VisualBoardController::class, 'index'])->name('boards.index');
Route::get('/boards/{visualBoard}/image', [VisualBoardController::class, 'image'])->name('boards.image');

Route::middleware('auth')->group(function () {
    Route::post('/boards', [VisualBoardController::class, 'store'])
        ->middleware('throttle:10,1')->name('boards.store');
    Route::patch('/boards/{visualBoard}', [VisualBoardController::class, 'update'])->name('boards.update');
    Route::delete('/boards/{visualBoard}', [VisualBoardController::class, 'destroy'])->name('boards.destroy');
});

// Code Exchange — public gallery; only a snippet's owner can modify it.
Route::get('/code-exchange', [\App\Http\Controllers\CodeExchangeController::class, 'index'])
    ->name('code-exchange.index');

Route::middleware('auth')->group(function () {
    Route::post('/code-exchange', [\App\Http\Controllers\CodeExchangeController::class, 'store'])
        ->middleware('throttle:10,1')->name('code-exchange.store');
    Route::patch('/code-exchange/{codeSnippet}', [\App\Http\Controllers\CodeExchangeController::class, 'update'])
        ->name('code-exchange.update');
    Route::delete('/code-exchange/{codeSnippet}', [\App\Http\Controllers\CodeExchangeController::class, 'destroy'])
        ->name('code-exchange.destroy');
});
