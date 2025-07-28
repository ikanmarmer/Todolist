<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $tasks = $user->tasks()->get();

        // Transform the tasks to include proper image URLs
        $tasks = $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'video' => $task->video,
                'image' => $task->image ? asset('storage/' . $task->image) : null,
                'deadline' => $task->deadline,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
            ];
        });

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
            'deadline' => $request->deadline ?? null, // Tambahkan ini

        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $imagePath = $user->email . '/tasks/' . $imageName;

            // Store the file
            $image->storeAs('public/' . $user->email . '/tasks', $imageName);

            // Save the path without 'public/' prefix
            $task->image = $user->email . '/tasks/' . $imageName;
            $task->save();
        }

        // Return task with proper image URL
        $data = $task->toArray();
        $data['image'] = $task->image ? asset('storage/' . $task->image) : null;

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

        // Transform the task to include proper image URL
        $taskData = $task->toArray();
        $taskData['image'] = $task->image ? asset('storage/' . $task->image) : null;

        return response()->json($taskData);
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

        // Update basic fields
        $task->update([
            'title' => $request->title,
            'description' => $request->description,
            'video' => $request->video ?? null,
            'deadline' => $request->deadline ?? null,
        ]);
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($task->image && Storage::disk('public')->exists($task->image)) {
                Storage::disk('public')->delete($task->image);
            }

            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $imagePath = $user->email . '/tasks/' . $imageName;

            // Store the new file
            $image->storeAs('public/' . $user->email . '/tasks', $imageName);

            // Update the image path
            $task->image = $user->email . '/tasks/' . $imageName;
            $task->save();
        }

        // Return task with proper image URL
        $data = $task->toArray();
        $data['image'] = $task->image ? asset('storage/' . $task->image) : null;

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

        // Delete associated image if exists
        if ($task->image && Storage::disk('public')->exists($task->image)) {
            Storage::disk('public')->delete($task->image);
        }

        $task->delete();
        return response()->json(['message' => 'Task deleted successfully']);
    }
}
