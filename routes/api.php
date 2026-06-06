<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SupportAgentController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Estadísticas del dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Listado de agentes de soporte
    Route::get('/support-agents', [SupportAgentController::class, 'index']);

    // Tickets
    Route::apiResource('tickets', TicketController::class);

    // Acciones especiales sobre tickets
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus']);
    Route::patch('/tickets/{ticket}/assign', [TicketController::class, 'assign']);

    // Comentarios de tickets
    Route::apiResource('tickets.comments', TicketCommentController::class)
        ->only(['index', 'store', 'destroy']);
});