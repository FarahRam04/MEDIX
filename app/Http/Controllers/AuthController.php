<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\registerRequest;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        if (!Auth::guard('patient')->attempt($request->only(['phone_number', 'password']))) {
            return response()->json(['message' => 'Invalid phone_number or password'],401);
        }
        $patient=Auth::guard('patient')->user();
        $token = $patient->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'Login successfully',
            'user'=>$patient,
            'token'=>$token
        ]);
    }

    public function register(RegisterRequest $request)
    {
        Hash::make($request['password']);
        $patient=Patient::create($request->validated());
        $token = $patient->createToken('auth_token for'. $patient->first_name,
            ['*'],now()->addDays(10))->plainTextToken;

        Auth::login($patient);
        return response()->json([
            'message'=>'Patient Registered successfully',
            'user'=>Auth::user(),
            'token'=>$token,
        ]);
    }

    public function  logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logout successfully',
        ]);
    }
}
