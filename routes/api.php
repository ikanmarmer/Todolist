<?php

use App\Http\Controllers\Api\V1\ContactController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\SubTaskController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\UserController;



Route::prefix('V1')->group(function () {
    Route::post('/contact', [ContactController ::class,'send']);
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

    Route::get('/plans', [PlanController::class, 'index']);
    Route::get('/plans/{id}', [PlanController::class, 'show']);
    Route::post('/payments/callback', [PaymentController::class, 'callback']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user/me', [UserController::class, 'me']);
        Route::post('/user/change-password', [AuthController::class, 'changePassword']);
        Route::post('/user/update', [UserController::class, 'update']);
        Route::delete('/user/delete-avatar', [UserController ::class, 'deleteAvatar']);
        Route::post('/user/change-password', [UserController::class, 'changePassword']);
        Route::apiResource('tasks', TaskController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::post('tasks/{id}', [TaskController::class, 'update']);
        Route::post('subtasks/change-status', [SubTaskController::class, 'changeStatus']);
        Route::apiResource('tasks.subtasks', SubTaskController::class)->only(['index', 'store', 'destroy']);
        Route::post('subtasks/{id}', [SubTaskController::class, 'update']);
        Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show', 'destroy']);
        Route::apiResource('payments', PaymentController::class)->only(['index', 'store', 'show']);
    });


// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {

    // User profile and task info
    Route::get('/user/profile', function (Request $request) {
        $user = $request->user()->load('plan');
        $tasksCount = $user->tasks()->count();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'plan' => $user->plan,
            'tasks_count' => $tasksCount,
            'tasks_remaining' => $user->remaining_tasks,
            'task_usage_percentage' => $user->task_usage_percentage,
            'can_create_task' => $user->canCreateTask(),
            'plan_expires_at' => $user->plan_expires_at
        ]);
    });

    Route::get('/user/tasks/count', [PaymentController::class, 'getUserTaskInfo']);

    // Orders
    Route::apiResource('orders', OrderController::class);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    Route::get('/user/current-plan', [OrderController::class, 'getCurrentPlanInfo']);

    // Payments
    Route::apiResource('payments', PaymentController::class);
    Route::get('/invoice/download/{order_id}', [PaymentController::class, 'downloadInvoice']);

    // Plans management (admin only - add middleware as needed)
    Route::apiResource('plans', PlanController::class)->except(['index', 'show']);
});

// Web routes for invoice download (if needed for direct browser access)
Route::middleware(['auth'])->group(function () {
    Route::get('/invoice/download/{order_id}', [PaymentController::class, 'downloadInvoice']);
});

Route::get('/user', function (Request $request) {
    return $request->user()->load('plan');
})->middleware('auth:sanctum');

});
