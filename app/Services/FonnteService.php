<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class FonnteService
{
    public function sendMessage(string $target, string $message): array
    {
        $token = config('services.fonnte.token');
        $url = config('services.fonnte.url', 'https://api.fonnte.com/send');

        if (!$token) {
            throw new RuntimeException('Token Fonnte belum diatur di .env.');
        }

        $normalizedTarget = $this->normalizeIndonesianPhoneNumber($target);

        $response = Http::withHeaders([
            'Authorization' => $token,
        ])->asForm()->post($url, [
            'target'  => $normalizedTarget,
            'message' => $message,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Fonnte gagal mengirim pesan: ' . $response->body());
        }

        $json = $response->json();

        if (is_array($json) && array_key_exists('status', $json)) {
            $status = $json['status'];

            if ($status === false || $status === 'false') {
                throw new RuntimeException(
                    'Fonnte menolak pesan: ' . ($json['reason'] ?? $json['message'] ?? 'Unknown error')
                );
            }
        }

        return is_array($json) ? $json : ['raw' => $response->body()];
    }

    private function normalizeIndonesianPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone);

        if (Str::startsWith($phone, '0')) {
            return '62' . substr($phone, 1);
        }

        if (Str::startsWith($phone, '8')) {
            return '62' . $phone;
        }

        return $phone;
    }
}