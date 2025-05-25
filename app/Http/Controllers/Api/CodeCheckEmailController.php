<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Employee;
use App\Models\ResetCodePassword;
use App\Models\User;
use Illuminate\Http\Request;

class CodeCheckEmailController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'code' => 'required|string|digits:6|',
            'email' => 'required|email|'
        ]);

        // البحث عن سجل يطابق البريد والرمز
        $passwordReset = ResetCodePassword::where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', now())
            ->first();

        // التحقق من وجود السجل
        if (!$passwordReset) {
            return response()->json([
                'message' => 'Invalid or expired code.'
            ], 422);
        }

        // تحديد نوع المستخدم
        $type = null;

        if (User::where('email', $request->email)->exists()) {
            $type = 'user';
        } elseif (Admin::where('email', $request->email)->exists()) {
            $type = 'admin';
        } elseif (Employee::where('email', $request->email)->exists()) {
            $type = 'employee';
        }

        // الكود صالح
        return response()->json([
            'code' => $passwordReset->code,
            'message' => 'passwords.code_is_valid',
            'type' => $type,
        ], 200);
    }}
