<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\LoginUserRequest;
use App\Http\Requests\Users\RegisterUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterUserRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);
        $token = $user->createToken('auth_token for u.' . $user->first_name)->plainTextToken;
        return response()->json([
            'message' => 'Doctor Registered successfully',
            'User' => $user,
            'token' => $token,
        ]);
    }

    public function login(LoginUserRequest $request)
    {
        $validated = $request->validated();
        $user = User::where('phone_number', $validated['phone_number'])->first();
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid phone_number or password'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successfully',
            'User' => $user,
            'token' => $token,
        ]);
    }


    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'logout successfully',
        ]);
    }
//    public function register(RegisterUserRequest $request)
//    {
//        $user=User::create($request->validated());
//        $token = $user->createToken('auth_token')->plainTextToken;
//        Auth::login($user);
//        return response()->json([
//            'message'=>'User Registered successfully',
//            'user'=>Auth::user(),
//            'token'=>$token,
//        ]);
//
//    }
//    public function login(LoginUserRequest $request)
//    {
//
//        if (!Auth::attempt($request->only('phone_number', 'password'))) { //attempt to authenticate the user=>Auth::user = this user
//            return response()->json(['message' => 'Invalid phone_number or password'], 401);
//        }
//        $user=Auth::user();
//        $token=$user->createToken('auth_token for '.$user->first_name)->plainTextToken;
//
//        return response()->json([
//            'message' => 'Login successfully',
//            'user'=>Auth::user(),
//            'token'=>$token
//        ]);
//
//    }
//    public function  logout(Request $request){
//        $request->user()->currentAccessToken()->delete();
//        return response()->json([
//            'message' => 'Logout successfully',
//        ]);
//    }

}
