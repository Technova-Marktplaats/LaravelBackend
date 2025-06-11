<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\CategoryController;

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
    Route::get('/items/{id}', [ItemController::class, 'show']);
    Route::post('/items', [ItemController::class, 'store']);
    Route::put('/items/{id}', [ItemController::class, 'update']);
    Route::delete('/items/{id}', [ItemController::class, 'destroy']);
    
    // Afbeelding verwijderen route
    Route::delete('/items/{itemId}/images/{imageId}', [ItemController::class, 'deleteImage']);
});


