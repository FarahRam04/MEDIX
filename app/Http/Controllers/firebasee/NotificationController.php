<?php

namespace App\Http\Controllers\firebasee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Google\Client;
class NotificationController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'fcm_token' => 'required|string',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        $client = new Client();
        $client->setAuthConfig(storage_path('firebase/firebase_credentials.json'));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->useApplicationDefaultCredentials();
        $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];

        $firebaseResponse = Http::withToken($accessToken)
            ->post('https://fcm.googleapis.com/v1/projects/healthcore-bdd68/messages:send', [
                'message' => [
                    'token' => $validated['fcm_token'],
                    'notification' => [
                        'title' => $validated['title'],
                        'body' => $validated['body'],
                    ],
                    'android' => [
                        'notification' => [
                            'sound' => 'clinic_app_notification_sound',
                            'channel_id' => 'channel_id',
                        ]
                    ]
                ]
            ]);


        if ($firebaseResponse->successful()) {
            return response()->json([
                'success' => true,
                'message' => 'Notification sent successfully',
                'firebase_response' => $firebaseResponse->json()
            ]);
        }
        else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification',
                'firebase_response' => $firebaseResponse->json()
            ], $firebaseResponse->status());
        }
    }
}
