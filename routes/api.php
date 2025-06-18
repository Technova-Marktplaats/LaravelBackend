<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\WatchlistController;
use App\Http\Controllers\NotificationController;

// Algemene OPTIONS route voor alle CORS preflight requests
Route::options('/{any}', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', 'http://localhost:5173')
        ->header('Access-Control-Allow-Credentials', 'true')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
})->where('any', '.*');

// Publieke routes (geen authenticatie vereist)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Publieke categorie routes

// Beveiligde routes (authenticatie vereist)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
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


    //Watchlist Routes
    Route::post('/watchlist/{id}/add', [WatchlistController::class, 'add']);
    Route::post('/watchlist/{id}/remove', [WatchlistController::class, 'remove']);

    // Notificatie Routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
});


