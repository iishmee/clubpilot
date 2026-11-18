<?php

use App\Http\Controllers\ServiceHoursController;
use Illuminate\Support\Facades\Route;

// F14 - service hours tracking. Loaded from routes/web.php with one require line.
Route::middleware('auth')->group(function () {
    Route::get('/service-hours', [ServiceHoursController::class, 'index'])->name('service-hours.index');
    Route::get('/service-hours/create', [ServiceHoursController::class, 'create'])->name('service-hours.create');
    Route::post('/service-hours', [ServiceHoursController::class, 'store'])->name('service-hours.store');
    Route::post('/service-hours/{entry}/review', [ServiceHoursController::class, 'review'])->name('service-hours.review');
});
