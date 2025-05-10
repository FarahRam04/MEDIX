<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class WhatsAppController extends Controller
{
// الدالة لإرسال رسالة عبر CallMeBot
public function sendWhatsApp($phone, $message)
{
    $apiKey = "3833666";  // استبدل "API_KEY_الخاص_بك" بالـ API key الخاص بك
    $phoneNumber = $phone; // الرقم بصيغة دولية، مثل: 201112345678

    // بناء الرابط باستخدام الـ API key والرقم
    $url = "https://api.callmebot.com/whatsapp.php?phone=$phoneNumber&text=" . urlencode($message) . "&apikey=$apiKey";

    // إرسال الطلب عبر HTTP
    $response = Http::get($url);

    // إرجاع الاستجابة (يمكنك عرضها أو معالجتها حسب الحاجة)
    return $response->body();
}

// دالة لاختبار إرسال رسالة
public function sendTestMessage()
{
    $code=rand(1000,9999);
    // استبدل "201112345678" بالرقم الذي تريد إرسال الرسالة إليه
    $this->sendWhatsApp("963951562400", "Thank you for using our App ,your code is ".$code);

    return "تم إرسال الرسالة!";
}
}
