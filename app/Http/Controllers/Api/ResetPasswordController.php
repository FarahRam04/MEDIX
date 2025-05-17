<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\ResetCodePassword;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'email' => 'required|email|email',
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
        $user = User::where('email', $request->email)->first();
        $admin = Admin::where('email', $request->email)->first();
        $employee = Employee::where('email', $request->email)->first();

        if ($user) {
            $user->update(['password' => Hash::make($request->password)]);
            $type = 'user';
        } elseif ($admin) {
            $admin->update(['password' => Hash::make($request->password)]);
            $type = 'admin';
        } elseif ($employee) {
            $employee->update(['password' => Hash::make($request->password)]);
            $type = 'employee';
        } else {
            return response()->json(['message' => 'Account not found.'], 404);
        }


        // حذف الكود بعد الاستخدام
        $passwordReset->delete();

        return response()->json([
            'message' => 'Password has been successfully reset.',
            'type' => $type,
        ], 200);
    }
}
