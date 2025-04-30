<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctors\LoginDoctorRequest;
use App\Http\Requests\Doctors\RegisterDoctorRequest;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DoctorAuth extends Controller
{
    public function register(RegisterDoctorRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        $doctor = Doctor::create($validated);
        $token = $doctor->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'Doctor Registered successfully',
            'Doctor' => $doctor,
            'token' => $token,
        ]);
    }

    public function login(LoginDoctorRequest $request)
    {
        $validated = $request->validated();
        $doctor = Doctor::where('phone_number', $validated['phone_number'])->first();
        if (!$doctor || !Hash::check($validated['password'], $doctor->password)) {
            return response()->json(['message' => 'Invalid phone_number or password'], 401);
        }

        $token = $doctor->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successfully',
            'doctor' => $doctor,
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


}
