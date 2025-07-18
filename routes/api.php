<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\SubTaskController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PlanController;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/oauth/google', [AuthController::class, 'oAuthCallUrl']);
        Route::get('/oauth/google/callback', [AuthController::class, 'oAuthCallback']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });
    Route::get('plans', [PlanController::class, 'index']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('tasks', TaskController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::post('tasks/{id}', [TaskController::class, 'update']);
        Route::apiResource('tasks.subtasks', SubTaskController::class)->only(['index', 'store', 'destroy']);
        Route::post('subtasks/{id}', [SubTaskController::class, 'update']);
        Route::post('subtasks', [SubTaskController::class, 'changeStatus']);
        Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show', 'destroy']);
        Route::apiResource('payments', PaymentController::class)->only(['index', 'store', 'show']);
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
