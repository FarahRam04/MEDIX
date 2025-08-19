<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\LoginUserRequest;
use App\Http\Requests\Users\RegisterUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserControllerAuth extends Controller
{
    public function register(RegisterUserRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);

        if ($request->hasFile('image')){
            $path = $request->file('image')->store('images', 'public');
            $validated['image'] = $path;
        }
        $user = User::create($validated);
        Auth::login($user);

        $token = $user->createToken('auth_token for u.' . $user->first_name)->plainTextToken;

        $user->refresh();

        return response()->json([
            'message'   => __('messages.register'),
            'token'     => $token,
        ]);
    }
    public function refreshToken(Request $request){
        $request->validate([
            'fcm_token'=> 'required|string'
        ]);
        $user = Auth::user();
        if ($user) {
            $user->update([
                'fcm_token' => $request->fcm_token,
                'fcm_token_updated_at' => now(),
            ]);
        }
        return response()->json([
            'message' => 'FCM token updated successfully',
        ]);
    }
    public function uploadImage(Request $request)
    {
        if ($request->hasFile('image')) {
            $request->validate([
                'image' => 'image|mimes:jpeg,png,jpg,gif,svg',
            ]);

            $path = $request->file('image')->store('images', 'public');
            $user = auth()->user();
            $user->image = $path;
            $user->save();

            return response()->json([
                'message' => 'Image uploaded successfully',

            ]);
        }
        return response()->json(['message' => 'Image not uploaded'], 422);
    }


    public function login(LoginUserRequest $request)
    {
        $validated = $request->validated();
        $user = User::where('email', $validated['email'])->first();
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        $user->update([
            'fcm_token' => $validated['fcm_token'],
            'fcm_token_updated_at' => now(),
        ]);
        $token = $user->createToken('auth_token for U.'. $user->first_name)->plainTextToken;

        return response()->json([
            'message' => 'Login successfully',
            'token' => $token,
        ]);
    }


    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'logout successfully',
        ]);
    }

}
