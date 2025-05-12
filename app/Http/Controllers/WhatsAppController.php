<?php
namespace App\Http\Controllers;

use App\Http\Requests\PhoneRequest;
use App\Models\VerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppController extends Controller
{
    private function transformPhoneNumber($phoneNumber)
    {
        if (substr($phoneNumber, 0, 1) == '0') {
            return'+963'.substr($phoneNumber, 1);
        }
        return $phoneNumber;
    }

    public function code(PhoneRequest $request): \Illuminate\Http\JsonResponse
    {
        $phone = $this->transformPhoneNumber(phoneNumber: $request->phone);

        $oldCode = VerificationCode::where('phone', $phone)->first();
        if ($oldCode) {
            $oldCode->delete();
        }

        $code = rand(1000, 9999);
        VerificationCode::create([
            'code' => $code,
            'phone' => $phone
        ]);

        $params = array(
            'token' => 'yat38sr4wuj9kdbg', // استبدله بالتوكن الخاص بك من UltraMsg
            'to' => $phone,
            'body' => 'your verification code is : ' . $code
        );

        try {
            $response = Http::asForm()->withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->post('https://api.ultramsg.com/instance117678/messages/chat', $params);

            if ($response->successful()) {
                return response()->json([
                    'message' => 'Verification Code has send successfully'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Unexpected HTTP status: ' . $response->status() . ' ' . $response->reason()
                ], 401);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verify(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'code' => 'required|numeric'
        ]);

        $phone = $this->transformPhoneNumber($request->phone);
        $code = $request->code;

        $verification = VerificationCode::where('phone', $phone)
            ->where('code', $code)
            ->first();

        if (!$verification) {
            return response()->json(['message' => 'رمز التحقق غير صحيح أو الرقم غير مطابق'], 401);
        }

        // خيار إضافي: حذف الكود بعد الاستخدام
        $verification->delete();


        return response()->json(['message' => 'تم التحقق من الرقم بنجاح'], 200);
    }


//// الدالة لإرسال رسالة عبر CallMeBot
//public function sendWhatsApp($phone, $message)
//{
//    $apiKey = "3833666";  // استبدل "API_KEY_الخاص_بك" بالـ API key الخاص بك
//    $phoneNumber = $phone; // الرقم بصيغة دولية، مثل: 201112345678
//
//    // بناء الرابط باستخدام الـ API key والرقم
//    $url = "https://api.callmebot.com/whatsapp.php?phone=$phoneNumber&text=" . urlencode($message) . "&apikey=$apiKey";
//
//    // إرسال الطلب عبر HTTP
//    $response = Http::get($url);
//
//    // إرجاع الاستجابة (يمكنك عرضها أو معالجتها حسب الحاجة)
//    return $response->body();
//}
//
//// دالة لاختبار إرسال رسالة
//public function sendTestMessage()
//{
//    $code=rand(1000,9999);
//    // استبدل "201112345678" بالرقم الذي تريد إرسال الرسالة إليه
//    $this->sendWhatsApp("963951562400", "Thank you for using our App ,your code is ".$code);
//
//    return "تم إرسال الرسالة!";
//}
}
