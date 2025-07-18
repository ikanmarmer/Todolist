<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\SubTask;

class SubTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $id = request()->route('task_id');

        if (!$id) {
            return response()->json(['error' => 'Task ID is required'], 400);
        }
        $user = Auth::user();
        $task = $user->tasks()->findOrFail($id);
        if (Auth::id() !== $task->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json($task->subtasks()->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $id = request()->route('task_id');

        if (!$id) {
            return response()->json(['error' => 'Task ID is required'], 400);
        }
        $user = Auth::user();
        $task = $user->tasks()->findOrFail($id);
        if (Auth::id() !== $task->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subtask = $task->subtasks()->create([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return response()->json($subtask, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $subtask = SubTask::findOrFail($id);
        $task = $subtask->task;
        if (Auth::id() !== $task->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subtask->update($request->only([
            'title',
            'description'
        ]));
        return response()->json($subtask);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $subtask = SubTask::findOrFail($id);
        $task = $subtask->task;
        if (Auth::id() !== $task->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $subtask->delete();
        return response()->json(['message' => 'Subtask deleted successfully']);
    }

    public function changeStatus(Request $request, string $id)
    {
        $subtask = SubTask::findOrFail($id);
        $task = $subtask->task;
        if (Auth::id() !== $task->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending, in_progress, completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subtask->status = $request->status;
        $subtask->save();

        return response()->json($subtask);
    }
}
