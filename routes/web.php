<?php

use App\Http\Controllers\Auth\SessionController;
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

Route::get('/welcome', function () {
    return response(File::get(public_path('frontend/index.html')), 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
    ]);
})->name('welcome');
