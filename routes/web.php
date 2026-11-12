<?php

use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view(auth()->check() ? 'dashboard' : 'auth.login');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::post('/logout', [SessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::view('/dashboard', 'dashboard')
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('events', EventController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::post('/events/{event}/submit', [EventController::class, 'submit'])->name('events.submit');
    Route::get('/event-reviews', [EventController::class, 'reviewIndex'])->name('events.reviews.index');
    Route::post('/events/{event}/review', [EventController::class, 'review'])->name('events.review');

    Route::get('/events/{event}/reservations/create', [ReservationController::class, 'create'])->name('events.reservations.create');
    Route::post('/events/{event}/reservations', [ReservationController::class, 'store'])->name('events.reservations.store');
    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::get('/venue-request-reviews', [ReservationController::class, 'reviewIndex'])->name('reservations.reviews.index');
    Route::post('/reservations/{reservation}/review', [ReservationController::class, 'review'])->name('reservations.review');

    Route::get('/venues', [VenueController::class, 'index'])->name('venues.index');
    Route::get('/venues/create', [VenueController::class, 'create'])->name('venues.create');
    Route::post('/venues', [VenueController::class, 'store'])->name('venues.store');
});

Route::get('/welcome', function () {
    return response(File::get(public_path('frontend/index.html')), 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
    ]);
})->name('welcome');
