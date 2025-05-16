<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ResetCodePassword;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|digits:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // العثور على السجل الذي يطابق البريد والرمز ولم ينتهِ صلاحيته
        $passwordReset = ResetCodePassword::where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$passwordReset) {
            return response()->json([
                'message' => 'Invalid or expired code.'
            ], 422);
        }

        // تحديث كلمة المرور
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // حذف الكود بعد الاستخدام
        $passwordReset->delete();

        return response()->json([
            'message' => 'Password has been successfully reset.'
        ], 200);
    }
}
