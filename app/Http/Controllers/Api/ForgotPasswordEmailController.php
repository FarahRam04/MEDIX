<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\EmailVerification;
use App\Models\Employee;
use App\Models\ResetCodePassword;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordEmailController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'email'
            => 'required|email',
        ]);

        // معرفة نوع المستخدم
        $type = null;

        if (User::where('email', $request->email)->exists()) {
            $type = 'user';
        } elseif (Admin::where('email', $request->email)->exists()) {
            $type = 'admin';
        } elseif (Employee::where('email', $request->email)->exists()) {
            $type = 'employee';
        }

        if (!$type) {
            return response()->json(['message' => 'Email not found.'], 404);
        }

        // حذف الأكواد القديمة
        ResetCodePassword::where('email', $request->email)->delete();

        // إنشاء كود عشوائي
        $code = random_int(100000, 999999);


        // حفظ الكود
        ResetCodePassword::updateOrCreate(
            ['email' => $request->email],
            [
                'code' => $code,
                'expires_at' => Carbon::now()->addMinutes(5),
            ]
        );

        // إرسال الكود بالإيميل
        Mail::raw("Your verification code is: $code", function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Reset password');
        });
        return response([
            'message' => 'Check your email, we sent the code.',
            'type' => $type,
        ], 200);
    }

}
//trans('passwords.sent')
