<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    private string $projectId;
    private string $credentialsPath;

    public function __construct()
    {
        $this->projectId      = env('FIREBASE_PROJECT_ID', '');
        $this->credentialsPath = base_path(env('FIREBASE_CREDENTIALS', 'storage/app/firebase-credentials.json'));
    }

    /**
     * Kirim push notification ke satu FCM token.
     */
    public function send(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        try {
            $accessToken = $this->getAccessToken();
            if (!$accessToken) return false;

            $response = Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send", [
                    'message' => [
                        'token'        => $fcmToken,
                        'notification' => [
                            'title' => $title,
                            'body'  => $body,
                        ],
                        'data'         => array_map('strval', $data),
                        'android'      => [
                            'notification' => [
                                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                                'channel_id'   => 'devora_notifications',
                            ],
                        ],
                        'apns' => [
                            'payload' => [
                                'aps' => [
                                    'sound' => 'default',
                                    'badge' => 1,
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('FCM send failed', ['status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('FCM exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim push ke banyak token sekaligus.
     */
    public function sendMultiple(array $fcmTokens, string $title, string $body, array $data = []): void
    {
        foreach ($fcmTokens as $token) {
            $this->send($token, $title, $body, $data);
        }
    }

    /**
     * Generate OAuth2 access token dari service account JSON.
     */
    private function getAccessToken(): ?string
    {
        $credentials = null;

        if (env('FIREBASE_CREDENTIALS_BASE64')) {
            $credentials = json_decode(base64_decode(env('FIREBASE_CREDENTIALS_BASE64')), true);
        } elseif (file_exists($this->credentialsPath)) {
            $credentials = json_decode(file_get_contents($this->credentialsPath), true);
        }

        if (!$credentials) {
            Log::error('Firebase credentials missing. File not found: ' . $this->credentialsPath . ' and FIREBASE_CREDENTIALS_BASE64 is empty.');
            return null;
        }

        $now = time();
        $payload = [
            'iss'   => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ];

        $jwt = $this->buildJwt($payload, $credentials['private_key']);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        if ($response->successful()) {
            return $response->json('access_token');
        }

        Log::error('Failed to get FCM access token', ['body' => $response->body()]);
        return null;
    }

    /**
     * Build JWT token untuk OAuth2.
     */
    private function buildJwt(array $payload, string $privateKey): string
    {
        $header  = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode($payload));

        $header  = str_replace(['+', '/', '='], ['-', '_', ''], $header);
        $payload = str_replace(['+', '/', '='], ['-', '_', ''], $payload);

        $signature = '';
        openssl_sign("$header.$payload", $signature, $privateKey, 'sha256WithRSAEncryption');
        $signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return "$header.$payload.$signature";
    }
}
