<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginAdminRequest;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuth extends Controller
{
    public function login(LoginAdminRequest $request)
    {

        if (!Auth::guard('admin')->attempt($request->only('email', 'password'))) { //attempt to authenticate the user=>Auth::user = this user
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        return response()->json([
            'message' => 'Login successfully',
            'user' => Auth::guard('admin')->user(),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate(); // بيلغي الجلسة القديمة نهائياً
        $request->session()->regenerateToken(); // بيولد CSRF Token جديد لزيادة الأمان

        return response()->json([
            'message' => 'Logout successfully'
        ]);
    }
}
