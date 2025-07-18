<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use app\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        $tasks = $user->tasks()->get();
        return response()->json($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $plan = $user->plan;

        if ($plan && $plan->task_limit > 0 && $user->tasks()->count() >= $plan->task_limit) {
            return response()->json(['message' => 'You have reached the maximum number of tasks for your plan'], 429);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video' => 'nullable|string',
            'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task = $user->tasks()->create([
            'title' => $request->title,
            'description' => $request->description,
            'video' => $request->video ?? null,
        ]);

        $image = $request->file('image');
        if ($image) {
            $imagePath = $user->email . '/tasks/' . $task->title;
            Storage::disk('public')->put($imagePath, $image->getContent());
            $task->image = $imagePath;
            $task->save();
        }

        $data = $task;
        $data['image'] = $task->image == null ? null : asset($task->image);

        return response()->json($data, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $task = $user->tasks()->findOrFail($id);
        if (Auth::id() !== $task->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $task->load('subtasks');
        return response()->json($task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $task = $user->tasks()->findOrFail($id);
        if (Auth::id() !== $task->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video' => 'nullable|string',
            'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task->update($request->only('title', 'description', 'video'));
        $image = $request->file('image');
        if ($image) {
            $imagePath = $user->email . '/tasks/' . $task->title;
            Storage::disk('public')->put($imagePath, $image->getContent());
            $imagePath =  Storage::url($imagePath);
            $task->image = $imagePath;
            $task->save();
        }

        $data = $task;
        $data['image'] = $task->image == null ? null : asset($task->image);

        return response()->json($data);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $task = $user->tasks()->findOrFail($id);
        if (Auth::id() !== $task->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $task->delete();
        return response()->json(['message' => 'Task deleted successfully']);
    }
}
