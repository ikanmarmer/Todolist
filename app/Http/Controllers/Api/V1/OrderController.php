<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Order;
use App\Models\Plan;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $orders = Order::where('user_id', $user->id)
                      ->with(['plan', 'payment'])
                      ->orderBy('created_at', 'desc')
                      ->get();
        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plan = Plan::findOrFail($request->plan_id);
        $currentPlan = $user->plan;

        // Check if user is trying to subscribe to the same plan
        if ($plan->id === $currentPlan->id) {
            return response()->json([
                'message' => 'You are already subscribed to this plan',
                'current_plan' => $currentPlan
            ], 400);
        }

        // Prevent downgrading to a plan with fewer tasks
        if ($plan->tasks_limit < $currentPlan->tasks_limit) {
            return response()->json([
                'message' => 'Cannot downgrade to a plan with fewer tasks',
                'current_plan' => $currentPlan,
                'selected_plan' => $plan
            ], 403);
        }

        // Check for existing pending orders for this plan
        $existingOrder = Order::where('user_id', $user->id)
            ->where('plan_id', $plan->id)
            ->where('status', 'pending')
            ->first();

        if ($existingOrder) {
            return response()->json([
                'message' => 'You already have a pending order for this plan',
                'order' => $existingOrder
            ], 400);
        }

        // Check current task usage
        $tasksCount = $user->tasks()->count();
        if ($tasksCount >= $currentPlan->tasks_limit) {
            return response()->json([
                'message' => 'Task limit reached for current plan',
                'tasks_count' => $tasksCount,
                'current_limit' => $currentPlan->tasks_limit,
                'current_plan' => $currentPlan,
                'suggested_upgrade' => $plan
            ], 400);
        }

        // Create new order
        $newOrder = Order::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'amount' => $plan->price,
            'expires_at' => now()->addHours(24), // Order expires in 24 hours
        ]);

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $newOrder->load('plan'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $order = Order::where('user_id', $user->id)
                     ->with(['plan', 'payment'])
                     ->findOrFail($id);

        return response()->json([
            'message' => 'Order retrieved successfully',
            'order' => $order
        ]);
    }

    /**
     * Cancel an order
     */
    public function cancel(string $id)
    {
        $user = Auth::user();
        $order = Order::where('user_id', $user->id)->findOrFail($id);

        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending orders can be cancelled'
            ], 400);
        }

        $order->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);

        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => $order
        ]);
    }

    /**
     * Get user's current plan and task usage
     */
    public function getCurrentPlanInfo()
    {
        $user = Auth::user();
        $currentPlan = $user->plan;
        $tasksCount = $user->tasks()->count();

        return response()->json([
            'current_plan' => $currentPlan,
            'tasks_used' => $tasksCount,
            'tasks_remaining' => max(0, $currentPlan->tasks_limit - $tasksCount),
            'usage_percentage' => ($tasksCount / $currentPlan->tasks_limit) * 100,
            'can_create_task' => $tasksCount < $currentPlan->tasks_limit
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $order = Order::where('user_id', $user->id)->findOrFail($id);

        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending orders can be updated'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'plan_id' => 'sometimes|required|exists:plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('plan_id')) {
            $newPlan = Plan::findOrFail($request->plan_id);
            $order->update([
                'plan_id' => $newPlan->id,
                'amount' => $newPlan->price
            ]);
        }

        return response()->json([
            'message' => 'Order updated successfully',
            'order' => $order->load('plan')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $order = Order::where('user_id', $user->id)->findOrFail($id);

        if ($order->status === 'completed') {
            return response()->json([
                'message' => 'Completed orders cannot be deleted'
            ], 400);
        }

        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully'
        ]);
    }
}
