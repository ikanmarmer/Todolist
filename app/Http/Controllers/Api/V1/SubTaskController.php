<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Task;
use App\Models\SubTask;
use Illuminate\Support\Facades\Validator;

class SubTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Task $task)
    {
        // Ensure task belongs to authenticated user
        if (Auth::id() !== $task->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Fetch subtasks ordered by creation date
        $subtasks = $task->subtasks()
                         ->orderBy('created_at', 'desc')
                         ->get();

        return response()->json($subtasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Task $task, Request $request)
    {
        // Ensure task belongs to authenticated user
        if (Auth::id() !== $task->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate input data
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Create subtask with default status 'pending'
        $subtask = $task->subtasks()->create([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'status'      => 'pending',
        ]);

        return response()->json($subtask, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $subtask = SubTask::find($id);
        $task = $subtask->task;

        if (Auth::id() !== $task->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($subtask);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $subtask = SubTask::findorFail($id);
        if (! $subtask) {
            return response()->json(['message' => 'Subtask not found'], 404);
        }
        $task = $subtask->task;

        if (Auth::id() !== $task->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $subtask->update($data);

        return response()->json($subtask);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $subtask = SubTask::find($id);
        $task = $subtask->task;

        if (Auth::id() !== $task->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $subtask->delete();
        return response()->json(['message' => 'Subtask deleted successfully']);
    }

    /**
     * Change subtask status (for drag and drop functionality)
     */
    public function changeStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'subtask_id' => 'required|exists:sub_tasks,id',
                'status' => 'required|in:pending,in_progress,completed',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Gunakan firstOrFail untuk mendapatkan subtask atau langsung error
            $subtask = SubTask::with('task')->find($request->subtask_id);

            if (!$subtask) {
                return response()->json(['error' => 'Subtask not found'], 404);
            }

            // Pastikan relasi task tersedia
            if (!$subtask->task) {
                return response()->json(['error' => 'Associated task not found'], 404);
            }

            $task = $subtask->task;

            // Pastikan task dimiliki oleh user yang login
            if (Auth::id() !== $task->user_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Update subtask status
            $subtask->status = $request->status;
            $subtask->save();

            return response()->json([
                'message' => 'Status updated successfully',
                'subtask' => $subtask
            ]);

        } catch (\Exception $e) {
            Log::error("Error changing subtask status: " . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }
}
