<?php

use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\ClubProposalController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\EquipmentController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

// Home
Route::get('/', function () {
    return view(Auth::check() ? 'dashboard' : 'auth.login');
})->name('home');

// ==========================================
// Authentication
// ==========================================

Route::middleware('guest')->group(function () {

    Route::get('/login', [SessionController::class, 'create'])
        ->name('login');

    Route::post('/login', [SessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');

});

// Logout
Route::post('/logout', [SessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Dashboard
Route::view('/dashboard', 'dashboard')
    ->middleware('auth')
    ->name('dashboard');


// ==========================================
// Authenticated Application Routes
// ==========================================

Route::middleware('auth')->group(function () {

    // ==========================================
    // F01 - Club Recognition and Approval
    // ==========================================

    Route::get('/club-proposals', [ClubProposalController::class, 'index'])
        ->name('club-proposals.index');

    Route::get('/club-proposals/create', [ClubProposalController::class, 'create'])
        ->name('club-proposals.create');

    Route::post('/club-proposals', [ClubProposalController::class, 'store'])
        ->name('club-proposals.store');

    Route::get('/club-proposals/{clubProposal}', [ClubProposalController::class, 'show'])
        ->name('club-proposals.show');

    Route::post('/club-proposals/{clubProposal}/approve', [ClubProposalController::class, 'approve'])
        ->name('club-proposals.approve');

    Route::post('/club-proposals/{clubProposal}/reject', [ClubProposalController::class, 'reject'])
        ->name('club-proposals.reject');

    Route::get('/club-proposals/{clubProposal}/constitution', [ClubProposalController::class, 'downloadConstitution'])
        ->name('club-proposals.constitution');

    Route::get('/clubs', [ClubProposalController::class, 'directory'])
        ->name('clubs.directory');


    // ==========================================
    // F02 - Membership Management
    // ==========================================

    Route::get('/memberships', [MembershipController::class, 'index'])
        ->name('memberships.index');

    Route::get('/memberships/create', [MembershipController::class, 'create'])
        ->name('memberships.create');

    Route::post('/memberships', [MembershipController::class, 'store'])
        ->name('memberships.store');

    Route::get('/memberships/{membership}', [MembershipController::class, 'show'])
        ->name('memberships.show');

    Route::post('/clubs/{club}/memberships/invite', [MembershipController::class, 'invite'])
        ->name('memberships.invite');

    Route::post('/memberships/{membership}/accept', [MembershipController::class, 'acceptInvitation'])
        ->name('memberships.accept');

    Route::post('/memberships/{membership}/approve', [MembershipController::class, 'approve'])
        ->name('memberships.approve');

    Route::post('/memberships/{membership}/reject', [MembershipController::class, 'reject'])
        ->name('memberships.reject');

    Route::post('/memberships/{membership}/deactivate', [MembershipController::class, 'deactivate'])
        ->name('memberships.deactivate');

    Route::post('/memberships/{membership}/graduate', [MembershipController::class, 'graduate'])
        ->name('memberships.graduate');

    Route::post('/memberships/{membership}/reactivate', [MembershipController::class, 'reactivate'])
        ->name('memberships.reactivate');


    // ==========================================
    // F05 - Equipment Lending: Loan Management
    // ==========================================

    Route::get('/loans', [LoanController::class, 'index'])
        ->name('loans.index');

    Route::get('/loans/create', [LoanController::class, 'create'])
        ->name('loans.create');

    Route::post('/loans', [LoanController::class, 'store'])
        ->name('loans.store');

    Route::get('/loans/{loan}', [LoanController::class, 'show'])
        ->name('loans.show');

    Route::post('/loans/{loan}/approve', [LoanController::class, 'approve'])
        ->name('loans.approve');

    Route::post('/loans/{loan}/reject', [LoanController::class, 'reject'])
        ->name('loans.reject');

    Route::post('/loans/{loan}/issue', [LoanController::class, 'issue'])
        ->name('loans.issue');

    Route::post('/loans/{loan}/return', [LoanController::class, 'returnEquipment'])
        ->name('loans.return');

    Route::post('/loans/{loan}/cancel', [LoanController::class, 'cancel'])
        ->name('loans.cancel');


    // ==========================================
    // F05 - Equipment Inventory Management
    // ==========================================

    // Equipment directory
    Route::get('/equipment', [EquipmentController::class, 'index'])
        ->name('equipment.index');

    // Add equipment form
    Route::get('/equipment/create', [EquipmentController::class, 'create'])
        ->name('equipment.create');

    // Save new equipment
    Route::post('/equipment', [EquipmentController::class, 'store'])
        ->name('equipment.store');

    // View equipment details
    Route::get('/equipment/{equipment}', [EquipmentController::class, 'show'])
        ->name('equipment.show');

    // Edit equipment form
    Route::get('/equipment/{equipment}/edit', [EquipmentController::class, 'edit'])
        ->name('equipment.edit');

    // Update equipment
    Route::put('/equipment/{equipment}', [EquipmentController::class, 'update'])
        ->name('equipment.update');

    // Deactivate equipment
    Route::post('/equipment/{equipment}/deactivate', [EquipmentController::class, 'deactivate'])
        ->name('equipment.deactivate');

    // Reactivate equipment
    Route::post('/equipment/{equipment}/activate', [EquipmentController::class, 'activate'])
        ->name('equipment.activate');

});


// ==========================================
// Welcome Page
// ==========================================

Route::get('/welcome', function () {
    return response(File::get(public_path('frontend/index.html')), 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
    ]);
})->name('welcome');
