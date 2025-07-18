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
        $orders = Order::where('user_id', $user->id)->with('plan')->get();
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

        $plan =  Plan::findOrFail($request->plan_id);
        $currentPlan = Plan::find($user->plan_id);
        if ($plan->id === $currentPlan->id) {
            return response()->json(['message' => 'You are already subscribed to this plan'], 400);
        }

        if ($plan->tasks_limit < $currentPlan->tasks_limit) {
            return response()->json(['message' => 'Cannot downgrade to a plan with fewer tasks'], 403);
        }

        $order = Order::where('user_id', $user->id)
            ->where('plan_id', $plan->id)
            ->first();
        if ($order) {
            return response()->json(['message' => 'You have already subscribed to this plan'], 400);
        }
        $newOrder = Order::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'amount' => $plan->price,
        ]);

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $newOrder,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $order = Order::where('user_id', $user->id)->findOrFail($id);
        if ($user->id !== $order->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json(['message' => 'Order deleted successfully']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
