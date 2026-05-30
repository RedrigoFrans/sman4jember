<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    private string $projectId;
    private ?array $credentials = null;

    public function __construct()
    {
        $this->loadCredentials();
        $this->projectId = env('FIREBASE_PROJECT_ID', $this->credentials['project_id'] ?? '');
    }

    private function loadCredentials(): void
    {
        $credentialsPath = base_path(env('FIREBASE_CREDENTIALS', 'storage/app/firebase-credentials.json'));

        if (env('FIREBASE_CREDENTIALS_BASE64')) {
            $base64 = str_replace(' ', '+', env('FIREBASE_CREDENTIALS_BASE64'));
            $decoded = json_decode(base64_decode($base64), true);
            $this->credentials = is_array($decoded) ? $decoded : null;
        } elseif (is_file($credentialsPath)) {
            $decoded = json_decode(file_get_contents($credentialsPath), true);
            $this->credentials = is_array($decoded) ? $decoded : null;
        }

        if (!$this->credentials) {
            Log::error('Firebase credentials missing. File not found: ' . $credentialsPath . ' and FIREBASE_CREDENTIALS_BASE64 is empty.');
        }
    }

    /**
     * Kirim push notification ke satu FCM token.
     */
    public function send(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (empty($this->projectId)) {
            Log::error('FCM send failed: FIREBASE_PROJECT_ID is empty.');
            return false;
        }

        try {
            $accessToken = $this->getAccessToken();
            if (!$accessToken) return false;

            $messagePayload = [
                'token'        => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'android'      => [
                    'notification' => [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'channel_id'   => 'devora_notifications_v2',
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
            ];

            if (!empty($data)) {
                $messagePayload['data'] = array_map('strval', $data);
            }

            $response = Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send", [
                    'message' => $messagePayload
                ]);

            if ($response->successful()) {
                return true;
            }

            $responseBody = $response->json();
            if ($response->status() === 404 && isset($responseBody['error']['details'])) {
                foreach ($responseBody['error']['details'] as $detail) {
                    if (($detail['errorCode'] ?? '') === 'UNREGISTERED') {
                        \App\Models\FcmToken::where('token', $fcmToken)->delete();
                        Log::info("Deleted unregistered/invalid FCM token: " . $fcmToken);
                        break;
                    }
                }
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
        if (!$this->credentials) {
            return null;
        }

        $now = time();
        $payload = [
            'iss'   => $this->credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ];

        $jwt = $this->buildJwt($payload, $this->credentials['private_key']);

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