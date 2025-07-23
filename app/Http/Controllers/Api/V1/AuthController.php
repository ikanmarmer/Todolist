<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // CARI PLAN BERDASARKAN NAMA PLAN, BUKAN NAMA USER
            $freePlan = Plan::where('name', 'Free')->first();

            if (!$freePlan) {
                Log::error('Free plan not found in database');
                return response()->json(['error' => 'Free plan not found'], 500);
            }

            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => bcrypt($request->input('password')),
                'plan_id' => $freePlan->id,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully',
                'user' => $user,
                'token' => $token,
            ], 201);

        } catch (\Exception $e) {
            // LOG ERROR UNTUK DEBUGGING
            Log::error('Registration error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'User logged in successfully',
                'user' => $user,
                'token' => $token,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    public function me()
    {
        try {
            return response()->json(Auth::user());
        } catch (\Exception $e) {
            Log::error('Me error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'User logged out successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    public function oAuthUrl()
    {
        try {
            $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
            return response()->json(['url' => $url]);
        } catch (\Exception $e) {
            Log::error('OAuth URL error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    public function oAuthCallBack(Request $request) // TAMBAHKAN PARAMETER REQUEST
    {
        try {
            $socialUser = Socialite::driver('google')->stateless()->user();

            $existingUser = User::where('email', $socialUser->getEmail())->first();

            if ($existingUser) {
                // Update avatar jika perlu
                $existingUser->update([
                    'avatar' => $socialUser->avatar ?? $socialUser->getAvatar(),
                ]);

                $token = $existingUser->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'message' => 'User logged in successfully',
                    'user' => $existingUser,
                    'token' => $token,
                ], 200);
            }

            // Buat user baru jika belum ada
            $freePlan = Plan::where('name', 'Free')->first();

            if (!$freePlan) {
                Log::error('Free plan not found during OAuth registration');
                return response()->json(['error' => 'Default plan not found'], 500);
            }

            $newUser = User::create([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'password' => null,
                'plan_id' => $freePlan->id,
                'avatar' => $socialUser->avatar ?? $socialUser->getAvatar(),
            ]);

            $token = $newUser->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully',
                'user' => $newUser,
                'token' => $token,
            ], 201);

        } catch (\Exception $e) {
            Log::error('OAuth callback error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Authentication failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
