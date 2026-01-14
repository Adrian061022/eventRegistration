<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;

// Without authentication
Route::get('/ping', function () {return response()->json(['message' => 'API működik!']);});
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);


Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [UserController::class, 'me']);
    Route::put('/me', [UserController::class, 'updateMe']);
    Route::post('/logout', [UserController::class, 'logout']);
    
    Route::prefix('events')->group(function () {
        Route::get('/', [EventController::class, 'index']);
        Route::get('/upcoming', [EventController::class, 'upcoming']);
        Route::get('/past', [EventController::class, 'past']);
        Route::get('/filter', [EventController::class, 'filter']);

        Route::post('/', [EventController::class, 'store']);
        Route::put('/{event}', [EventController::class, 'update']);
        Route::delete('/{event}', [EventController::class, 'destroy']);

        Route::post('{event}/register', [RegistrationController::class, 'register'])->withTrashed();
        Route::delete('{event}/unregister', [RegistrationController::class, 'unregister'])->withTrashed();
        Route::delete('{event}/users/{user}', [RegistrationController::class, 'adminRemoveUser'])->withTrashed(); 
    });

        Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
    });

    });