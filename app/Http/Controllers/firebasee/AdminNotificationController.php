<?php

namespace App\Http\Controllers\firebasee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    private function sendFCM($fcm_token, $title, $body)
    {
        $client = new \Google\Client();
        $client->setAuthConfig(storage_path('firebase/firebase_credentials.json'));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->useApplicationDefaultCredentials();
        $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];

        Http::withToken($accessToken)->post('https://fcm.googleapis.com/v1/projects/healthcore-bdd68/messages:send', [
            'message' => [
                'token' => $fcm_token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
            ]
        ]);
    }

}
