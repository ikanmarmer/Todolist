<?php

// app/Http/Middleware/CheckTaskLimit.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTaskLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Load user's plan
        $user->load('plan');
        $tasksCount = $user->tasks()->count();

        // Check if user has reached task limit
        if ($tasksCount >= $user->plan->tasks_limit) {
            return response()->json([
                'error' => 'Task limit reached',
                'message' => 'You have reached the maximum number of tasks for your current plan',
                'current_plan' => $user->plan,
                'tasks_used' => $tasksCount,
                'tasks_limit' => $user->plan->tasks_limit,
                'upgrade_required' => true
            ], 403);
        }

        // Add task info to request for use in controllers
        $request->merge([
            'user_task_info' => [
                'current_plan' => $user->plan,
                'tasks_used' => $tasksCount,
                'tasks_remaining' => $user->plan->tasks_limit - $tasksCount,
                'usage_percentage' => ($tasksCount / $user->plan->tasks_limit) * 100
            ]
        ]);

        return $next($request);
    }
}
