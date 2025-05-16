<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ResetCodePassword;
use Illuminate\Http\Request;

class CodeCheckController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'code' => 'required|string|digits:6|exists:reset_code_passwords',
            'email' => 'required|email|exists:users'
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

        // الكود صالح
        return response()->json([
            'code' => $passwordReset->code,
            'message' => 'passwords.code_is_valid'
        ], 200);
    }}
