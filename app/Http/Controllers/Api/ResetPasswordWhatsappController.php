<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResetPasswordWhatsappController extends Controller
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
            'code' => 'required|string|digits:4',
            'password' => 'required|string|min:8|confirmed', // password_confirmation لازم يتأكد
        ]);

        $phone = $this->transformPhoneNumber($request->phone);

        $verification = VerificationCode::where('phone', $phone)
            ->where('code', $request->code)
            ->where('type', 'reset_password')
            ->Where('expires_at', '>', now())
            ->first();

        if (!$verification) {
            return response()->json(['message' => 'Invalid or expired verification code'], 401);
        }
        $user = User::where('phone_number', $phone)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();
        $verification->delete();
        return response()->json(['message' => 'Password reset successfully'], 200);
    }
}
