<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanExpiry
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if user's plan has expired
        if ($user->isPlanExpired()) {
            // Downgrade to free plan
            $freePlan = \App\Models\Plan::where('price', 0)->first();
            if ($freePlan) {
                $user->update([
                    'plan_id' => $freePlan->id,
                    'plan_expires_at' => null,
                    'tasks_used' => 0
                ]);
            }

            return response()->json([
                'error' => 'Plan expired',
                'message' => 'Your premium plan has expired. You have been downgraded to the free plan.',
                'current_plan' => $user->fresh()->plan,
                'upgrade_required' => true
            ], 402); // Payment Required
        }

        return $next($request);
    }
}
