<?php

namespace App\Services;

use Google\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NotificationService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function sendFCMNotification(string $token, string $title, string $body)
    {
        // تحقق من صحة التوكين (مثلاً ألا يكون فارغ أو قصير جداً)
        if (!$token || strlen($token) < 10) {
            return null; // توكين غير صالح لا ترسل
        }

        // تهيئة Google Client
        $client = new Client();
        $client->setAuthConfig(storage_path('firebase/firebase_credentials.json'));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->useApplicationDefaultCredentials();
        $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];

        // تجهيز بيانات الإشعار
        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'android' => [
                    'notification' => [
                        'sound' => 'clinic_app_notification_sound',
                        'channel_id' => 'channel_id',
                    ],
                ],
            ],
        ];

        // إرسال الإشعار إلى Firebase
        $response = Http::withToken($accessToken)
            ->post('https://fcm.googleapis.com/v1/projects/healthcore-bdd68/messages:send', $payload);

        // تحقق من حالة الاستجابة
        if (!$response->successful()) {
            $error = $response->json();

            // إذا التوكين غير صالح، نحذفه من قاعدة البيانات
            if (
                isset($error['error']['status']) &&
                in_array($error['error']['status'], ['UNREGISTERED', 'INVALID_ARGUMENT'])
            ) {
                $user = User::where('fcm_token', $token)->first();
                if ($user) {
                    $user->fcm_token = null;
                    $user->fcm_token_updated_at = null;
                    $user->save();
                }
            }
        }

        return $response;
    }

    // دالة send تستقبل الطلب من ال Frontend وتستخدم الدالة المستقلة
    public function send(Request $request)
    {
        $validated = $request->validate([
            'fcm_token' => 'required|string',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        $response = $this->sendFCMNotification(
            $validated['fcm_token'],
            $validated['title'],
            $validated['body']
        );

        if ($response && $response->successful()) {
            return response()->json([
                'success' => true,
                'message' => 'Notification sent successfully',
                'firebase_response' => $response->json(),
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification',
                'firebase_response' => $response ? $response->json() : null,
            ], $response ? $response->status() : 500);
        }
    }
}
