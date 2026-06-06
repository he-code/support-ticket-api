<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SupportAgentController;

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('tickets', TicketController::class);

    Route::patch('/tickets/{ticket}/assign', [TicketController::class, 'assign']);
    
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus']);

    Route::get('/support-agents', [SupportAgentController::class, 'index']);
    
    Route::apiResource('tickets.comments', TicketCommentController::class)
        ->only(['index', 'store', 'destroy']);
});