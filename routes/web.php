<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

// Google OAuth routes (hebben sessies nodig)
Route::get('/api/auth/google/redirect', [AuthController::class, 'redirectToGoogle']);
Route::get('/api/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
