<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ReservationController;

// Publieke routes (geen authenticatie vereist)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Publieke categorie routes

// Beveiligde routes (authenticatie vereist)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{slug}', [CategoryController::class, 'show']);
    Route::get('/categories/{categorySlug}/items', [CategoryController::class, 'getItemsByCategory']);

    // Item routes - nu beveiligd met authenticatie
    Route::get('/items', [ItemController::class, 'index']);
    Route::get('/items/my-items', [ItemController::class, 'myItems']);
    Route::get('/items/{id}', [ItemController::class, 'show']);
    Route::post('/items', [ItemController::class, 'store']);
    Route::put('/items/{id}', [ItemController::class, 'update']);
    Route::delete('/items/{id}', [ItemController::class, 'destroy']);
    
    // Afbeelding verwijderen route
    Route::delete('/items/{itemId}/images/{imageId}', [ItemController::class, 'deleteImage']);

    // Reservering routes
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::get('/reservations/my-items', [ReservationController::class, 'myItemReservations']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::post('/reservations/{id}/approve', [ReservationController::class, 'approve']);
    Route::post('/reservations/{id}/reject', [ReservationController::class, 'reject']);
    Route::delete('/reservations/{id}', [ReservationController::class, 'destroy']);
    Route::get('/debug/reservations', [ReservationController::class, 'debug']); // Tijdelijk
});


