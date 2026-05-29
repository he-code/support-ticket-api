<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::delete('tickets/{ticket}', [TicketController::class, 'destroy'])
    ->middleware('auth:sanctum')
    ->name('tickets.destroy');


Route::middleware('auth:sanctum')->group(function () {

    //Route::post('/tickets', [TicketController::class, 'store']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('tickets', TicketController::class);

    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus']);
    });
Route::middleware('auth:sanctum')->get('/test-auth', function (Request $request) {
    return $request->user();
});