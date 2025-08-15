<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\LoginUserRequest;
use App\Http\Requests\Users\RegisterUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserControllerAuth extends Controller
{
    public function register(RegisterUserRequest $request)
    {
        $validated = $request->validated();
        $validated['fcm_token_updated_at'] = now(); // إذا العمود موجود
        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        Auth::login($user);

        $this->uploadImage($request);

        $token = $user->createToken('auth_token for u.' . $user->first_name)->plainTextToken;

        $user->refresh();

        return response()->json([
            'message' => 'user Registered successfully',
            'User' => $user,
            'token' => $token,
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
    public function uploadImage(Request $request){
        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg',
        ]);
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
            $user = User::find(Auth::id());
            $user->image = asset('storage/' . $imagePath);
            $user->save();

            return response()->json([
                'message' => 'Image uploaded successfully',
                'path' => $user->image,
                'user_id'=>$user->id,
                'user_name'=>$user->first_name,

                ]);
        }
        return response()->json(['message' => 'Image not uploaded'],422);
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
            'User' => $user,
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
