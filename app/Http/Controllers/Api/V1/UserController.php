<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user()->load('plan');
        return response()->json($this->formatUserResponse($user));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->hasFile('avatar')) {
            // Delete old avatar if it's a local file (not Google avatar)
            if ($user->avatar && !filter_var($user->avatar, FILTER_VALIDATE_URL)) {
                if (Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }
            }

            // Store new avatar
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user' => $this->formatUserResponse($user)
        ]);
    }

    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        // Only delete local files, not Google avatars
        if ($user->avatar && !filter_var($user->avatar, FILTER_VALIDATE_URL)) {
            if (Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
        }

        $user->avatar = null;
        $user->save();

        return response()->json([
            'message' => 'Avatar berhasil dihapus',
            'user' => $this->formatUserResponse($user)
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();
        $user->update([
            'password' => bcrypt($request->new_password),
        ]);

        return response()->json(['message' => 'Password berhasil diubah']);
    }

    // app/Http/Controllers/Api/V1/UserController.php
    public function profile(Request $request)
    {
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
    }

    /**
     * Format user response with proper avatar URL
     */
    private function formatUserResponse($user)
    {
        $avatarUrl = null;

        if ($user->avatar) {
            // Check if it's a Google avatar (external URL)
            if (filter_var($user->avatar, FILTER_VALIDATE_URL)) {
                $avatarUrl = $user->avatar;
            } else {
                // It's a local stored file
                $avatarUrl = Storage::disk('public')->url($user->avatar);
            }
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $avatarUrl,
            'plan_id' => $user->plan_id ?? null,
            'status' => $user->status ?? 'free',
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}
