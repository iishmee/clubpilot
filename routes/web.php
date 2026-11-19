<?php

use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\VolunteerRecruitmentController;
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

    Route::get('/volunteer-opportunities', [VolunteerRecruitmentController::class, 'index'])->name('volunteers.index');
    Route::get('/events/{event}/volunteer-opportunities/create', [VolunteerRecruitmentController::class, 'create'])->name('volunteers.create');
    Route::post('/events/{event}/volunteer-opportunities', [VolunteerRecruitmentController::class, 'store'])->name('volunteers.store');
    Route::get('/volunteer-opportunities/{opportunity}', [VolunteerRecruitmentController::class, 'show'])->name('volunteers.show');
    Route::post('/volunteer-opportunities/{opportunity}/apply', [VolunteerRecruitmentController::class, 'apply'])->name('volunteers.apply');
    Route::post('/volunteer-opportunities/{opportunity}/close', [VolunteerRecruitmentController::class, 'close'])->name('volunteers.close');
    Route::post('/volunteer-applications/{application}/review', [VolunteerRecruitmentController::class, 'reviewApplication'])->name('volunteers.applications.review');
    require __DIR__.'/volunteers.php';
});

Route::get('/welcome', function () {
    return response(File::get(public_path('frontend/index.html')), 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
    ]);
})->name('welcome');

// F12 - Volunteer shift scheduling
Route::middleware('auth')->group(function () {
    Route::get('/volunteer-opportunities/{opportunity}/shifts', [\App\Http\Controllers\ShiftController::class, 'index'])->name('shifts.index');
    Route::post('/volunteer-opportunities/{opportunity}/shifts', [\App\Http\Controllers\ShiftController::class, 'store'])->name('shifts.store');
    Route::post('/shifts/{shift}/assignments', [\App\Http\Controllers\ShiftController::class, 'assign'])->name('shifts.assign');
    Route::match(['post', 'delete'], '/shift-assignments/{assignment}/remove', [\App\Http\Controllers\ShiftController::class, 'unassign'])->name('shifts.unassign');
    Route::match(['post', 'delete'], '/shifts/{shift}/delete', [\App\Http\Controllers\ShiftController::class, 'destroy'])->name('shifts.destroy');
    Route::get('/my-shifts', [\App\Http\Controllers\ShiftController::class, 'mine'])->name('shifts.mine');
});
