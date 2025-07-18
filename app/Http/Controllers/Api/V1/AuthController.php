<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $freePlan = Plan::where('name', $request->name)->first();
        if (!$freePlan) {
            return response()->json(['error' => 'Default plan not found'], 500);
        }
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'plan_id' => $freePlan->id,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
        // Registration logic here
    }
    public function login(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
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

    }

    public function me(){
        return response()->json(Auth::user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'User logged out successfully'], 200);
    }

    public function oAuthUrl(){
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
        return response()->json(['url' => $url]);
    }

    public function oAuthCallBack(){
        $user = Socialite::driver('google')->stateless()->user();
        $existingUser = User::where('email', $user->getEmail())->first();
        if ($existingUser) {
            $token = $existingUser->createToken('auth_token')->plainTextToken;
            if ($existingUser){
                $token = $existingUser->createToken('auth_token')->plainTextToken;
                $existingUser->update([
                    'avatar' => $user ->avatar ?? $user->getAvatar(),
                ]);
                return response()->json([
                    'message' => 'User logged in successfully',
                    'user' => $existingUser,
                    'token' => $token,
                ],);
            } else {
                $freePlan = Plan::where('name', 'Free')->first();
                if (!$freePlan) {
                    return response()->json(['error' => 'Default plan not found'], 500);
                }
                $newUser = User::create([
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'password' => null,
                    'plan_id' => $freePlan->id,
                    'avatar' => $user->avatar ?? $user->getAvatar(),
                ]);
                $token = $newUser->createToken('auth_token')->plainTextToken;
                return response()->json([
                    'message' => 'User logged in successfully',
                    'user' => $newUser,
                    'token' => $token,
                ], 201);
            }
    }
    }


}
