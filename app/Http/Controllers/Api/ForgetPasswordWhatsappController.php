<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\VerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ForgetPasswordWhatsappController extends Controller
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
            'phone' => 'required|string|digits:10|exists:users,phone_number',

        ]);

        $phone = $this->transformPhoneNumber($request->phone);

        VerificationCode::where('phone', $phone)->where('type', 'reset_password')->delete();

        $code = random_int(1000, 9999);

        VerificationCode::updateOrCreate([
            'phone' => $phone,
            'code' => $code,
            'type' => 'reset_password',
            'expires_at' => now()->addMinutes(5),
        ]);

        $params = [
            'token' => '2p6uzuri0np9aapy', // استبدل بالتوكن الخاص بك
            'to' => $phone,
            'body' => 'Your password reset code is: ' . $code,
        ];

        try {
            $response = Http::asForm()->post('https://api.ultramsg.com/instance122057/messages/chat', $params);

            if ($response->successful()) {
                return response()->json(['message' => 'Verification code sent successfully'], 200);
            }

            return response()->json(['message' => 'Failed to send code', 'details' => $response->body()], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
