<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminAndEmployee\AddEmployeeRequest;
use App\Http\Requests\AdminAndEmployee\LoginAdminRequest;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAndEmployeeAuth extends Controller
{
    public function login(LoginAdminRequest $request)

    {
        $credentials = $request->only('email', 'password');

        if (Auth::guard('admin')->attempt($credentials)) {
            $admin = Auth::guard('admin')->user();
            $token = $admin->createToken('auth_token')->plainTextToken;
            return response()->json([
                'status' => true,
                'user_type' => $admin->getRoleNames()->first(),
                'user' => $admin,
                'token' => $token,
            ])->withCookie(cookie('laravel_session', session()->getId(),60));

        }

        if (Auth::guard('employee')->attempt($credentials)) {
            $employee = Auth::guard('employee')->user();
            $token = $employee->createToken('auth_token')->plainTextToken;
            return response()->json([
                'status' => true,
                'user_type' => $employee->getRoleNames()->first(),
                'user' => $employee,
                'token' => $token,
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Invalid credentials',
        ], 401)->withCookie(cookie('laravel_session', session()->getId(),60));
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'لم يتم تسجيل الدخول',
            ], 401);
        }
        $user->currentAccessToken()->delete();
        return response()->json([
            'status' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ])->withCookie(cookie()->forget('laravel_session'));
    }







}
