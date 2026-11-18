<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    // B (Browse) and R (Read).
    Route::middleware('abilities:blogs:read')->group(function () {
        Route::get('/blogs', [BlogController::class, 'browse']);
        Route::get('/blog/{id}', [BlogController::class, 'read'])->whereNumber('id');
    });

    // A (Add).
    Route::post('/blogs', [BlogController::class, 'add'])
        ->middleware('abilities:blogs:create');

    // E (Edit).
    Route::put('/blog/{id}', [BlogController::class, 'edit'])
        ->middleware('abilities:blogs:update')
        ->whereNumber('id');

    // D (Delete).
    Route::delete('/blog/{id}', [BlogController::class, 'delete'])
        ->middleware('abilities:blogs:delete')
        ->whereNumber('id');
});
