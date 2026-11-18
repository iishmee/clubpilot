<?php

use App\Http\Controllers\CertificateController;
use Illuminate\Support\Facades\Route;

// F15 - PDF participation certificates. Loaded from routes/web.php with one require line.
Route::middleware('auth')->group(function () {
    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::post('/volunteer-opportunities/{opportunity}/certificates', [CertificateController::class, 'store'])->name('certificates.store');
    Route::get('/certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');
});
