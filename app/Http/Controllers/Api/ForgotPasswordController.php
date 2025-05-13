<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ResetCodePassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendCodeResetPassword;

class ForgotPasswordController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|exists:users',
        ]);

        // حذف الأكواد القديمة
        ResetCodePassword::where('email', $request->email)->delete();

        // إنشاء كود عشوائي
        $data['code'] = mt_rand(100000, 999999);

        // حفظ الكود
        $codeData = ResetCodePassword::create($data);

        // إرسال الكود بالإيميل
       // Mail::to($request->email)->send(new SendCodeResetPassword($codeData->code));
        Mail::raw("Your verification code is: $codeData", function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Reset password');
        });
        return response(['message' =>trans('passwords.sent')], 200);
    }

}
//trans('passwords.sent')
