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

Route::get('/', [QuoteController::class, 'index'])->name('home');

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
    Route::get('/soundtrack', [FavoriteSongController::class, 'index'])->name('soundtrack.index');

    Route::post('/soundtrack', [FavoriteSongController::class, 'store'])->name('soundtrack.store');

    Route::put('/soundtrack/{favoriteSong}', [FavoriteSongController::class, 'update'])->name('soundtrack.update');

    Route::delete('/soundtrack/{favoriteSong}', [FavoriteSongController::class, 'destroy'])->name('soundtrack.destroy');

    Route::patch('/soundtrack/{favoriteSong}/repeat', [FavoriteSongController::class, 'repeat'])->name('soundtrack.repeat');

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
