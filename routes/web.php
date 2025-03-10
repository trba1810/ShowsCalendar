<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ShowsController;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::get('/calendar', function () {
    return Inertia::render('calendar');
})->name('CalendarPage');

Route::get('/movies/search', [MovieController::class, 'search'])->name('movies.search');
Route::get('/movies/{id}', [MovieController::class, 'show'])->name('movies.show');
Route::get('/shows/{id}', [ShowsController::class, 'GetShowById'])->name('shows.getshowbyid');
Route::get('/shows/monthly', [ShowsController::class, 'getMonthlyEpisodes'])->name('shows.monthly');

Route::middleware(['auth'])->group(function () {
    Route::get('/shows/following', [ShowsController::class, 'following']);
    Route::get('/shows/episodes', [ShowsController::class, 'getEpisodes']);
    Route::post('/shows/{id}/follow', [ShowsController::class, 'toggleFollow']);
    
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
