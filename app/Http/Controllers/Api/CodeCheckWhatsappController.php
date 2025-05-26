<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\VerificationCode;
use Illuminate\Http\Request;

class CodeCheckWhatsappController extends Controller
{
    private function transformPhoneNumber($phone)
    {
        if (substr($phone, 0, 1) === '0') {
            return '+963' . substr($phone, 1);
        }
        return $phone;
    }

    public function __invoke(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|numeric',
        ]);

        $phone = $this->transformPhoneNumber($request->phone);
        $code = $request->code;

        $verification = VerificationCode::where('phone', $phone)
            ->where('code', $code)
            ->where('type', 'reset_password')
            ->Where('expires_at', '>', now())
            ->first();

        if (!$verification) {
            return response()->json(['message' => 'Invalid or expired verification code'], 401);
        }



        return response()->json(['message' => 'Code verified successfully'], 200);
    }
}
