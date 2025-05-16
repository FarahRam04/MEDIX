<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailVerification;
use App\Models\ResetCodePassword;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendCodeResetPassword;

class ForgotPasswordController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'email'
            => 'required|email|exists:users',
        ]);

        // حذف الأكواد القديمة
        ResetCodePassword::where('email', $request->email)->delete();

        // إنشاء كود عشوائي
        $code = rand(100000, 999999);

        // حفظ الكود
        ResetCodePassword::updateOrCreate(
            ['email' => $request->email],
            [
                'code' => $code,
                'expires_at' => Carbon::now()->addMinutes(5),
            ]
        );

        // إرسال الكود بالإيميل
// Mail::to($request->email)->send(new SendCodeResetPassword($codeData->code));
        Mail::raw("Your verification code is: $code", function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Reset password');
        });
        return response(['message' =>'check email,we send the code.'], 200);
    }

}
//trans('passwords.sent')
