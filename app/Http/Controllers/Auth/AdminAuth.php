<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginAdminRequest;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuth extends Controller
{
    public function login(Request $request)

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

//    public function login(Request $request)
//    {
//        $credentials = $request->only('email', 'password');
//
//        if (Auth::guard('admin')->attempt($credentials)) {
//            $admin = Auth::guard('admin')->user();
//            return redirect()->intended('/admin/dashboard');
//        }
//
//        if (Auth::guard('employee')->attempt($credentials)) {
//            $employee = Auth::guard('employee')->user();
//            return redirect()->intended('/employee/dashboard');
//        }
//
//        return back()->withErrors([
//            'email' => 'بيانات الدخول غير صحيحة.',
//        ])->onlyInput('email');
//    }


}
