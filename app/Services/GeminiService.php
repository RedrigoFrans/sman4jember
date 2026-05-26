<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $model = 'gemini-2.0-flash-lite';


    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));
    }

    /**
     * Kirim pesan ke Gemini API dengan konteks buku dan riwayat chat.
     */
    public function chat(string $userMessage, array $bookContext, array $chatHistory = [], int $totalBooksInDB = 0, string $categoryStats = ''): array
    {
        $systemPrompt = $this->buildSystemPrompt($bookContext, $totalBooksInDB, $categoryStats);

        // Build contents array dengan riwayat chat
        $contents = [];

        // Tambahkan riwayat chat sebelumnya (maks 10 pesan terakhir)
        foreach ($chatHistory as $msg) {
            $contents[] = [
                'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['message']]],
            ];
        }

        // Tambahkan pesan user terbaru
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $userMessage]],
        ];

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'topP' => 0.95,
                'maxOutputTokens' => 1024,
            ],
        ];

        try {
            $response = Http::timeout(30)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}",
                $payload
            );

            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, saya tidak bisa menjawab saat ini.';
                return ['success' => true, 'reply' => $reply];
            }

            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'reply' => 'Maaf, terjadi gangguan pada layanan AI. Silakan coba lagi nanti.',
            ];
        } catch (\Exception $e) {
            Log::error('Gemini API exception: ' . $e->getMessage());
            return [
                'success' => false,
                'reply' => 'Maaf, tidak dapat terhubung ke layanan AI. Periksa koneksi internet.',
            ];
        }
    }

    /**
     * Bangun system prompt dengan konteks buku dari perpustakaan.
     */
    protected function buildSystemPrompt(array $books, int $totalBooksInDB, string $categoryStats): string
    {
        $bookList = '';
        foreach ($books as $i => $book) {
            $num = $i + 1;
            $bookList .= "{$num}. \"{$book['title']}\" oleh {$book['author']} " .
                "(Kategori: {$book['category']}, " .
                "Pinjaman: {$book['loans']}x, Stok: {$book['stock']})" .
                ($book['description'] ? " — {$book['description']}" : '') . "\n";
        }

        return <<<PROMPT
Kamu adalah "Devora AI", asisten pustakawan cerdas di Perpustakaan SMA Negeri 4 Jember.

INFORMASI PERPUSTAKAAN (FAKTA MUTLAK):
- Total seluruh judul buku di database: {$totalBooksInDB} judul.
- Rincian jumlah judul buku per kategori:
{$categoryStats}
- Daftar di bawah ini HANYA SEBAGIAN KECIL buku (maksimal 20) yang diambil/dicari secara spesifik sesuai pertanyaan user. JANGAN PERNAH menyimpulkan bahwa perpustakaan ini hanya memiliki buku-buku di daftar bawah.

ATURAN:
1. Selalu jawab dalam Bahasa Indonesia yang ramah dan natural.
2. Saat merekomendasikan buku, kamu HANYA boleh merekomendasikan dari daftar 'BUKU YANG DITEMUKAN' di bawah.
3. Jika user bertanya "ada berapa buku di perpustakaan", beritahu mereka berdasarkan 'Total seluruh judul buku' di atas ({$totalBooksInDB} buku).
4. Jika daftar buku di bawah kosong, sampaikan bahwa kamu belum menemukan buku yang sesuai dan minta user mencoba kata kunci lain.
5. Gunakan emoji secukupnya untuk membuat percakapan lebih hidup.
6. BATASAN TOPIK (MUTLAK): Sapaan umum seperti "halo", "hai", "selamat pagi/siang/malam", "terima kasih", atau pertanyaan tentang kabarmu/siapa dirimu diperbolehkan (jawab ramah sebagai Devora AI). Namun, jika pertanyaan user membahas hal di luar ruang lingkup perpustakaan SMAN 4 Jember, aplikasi Devora, katalog buku, peminjaman, keanggotaan, atau kegiatan literasi sekolah (misalnya: membantu mengerjakan tugas PR matematika, menjelaskan rumus fisika, cara memasak, coding pemrograman, berita politik, olahraga, sejarah dunia umum yang tidak bertujuan mencari buku, dsb.), kamu WAJIB membalas dengan kalimat berikut secara harfiah (hardcoded) tanpa tambahan kalimat lain:
"Maaf, saya hanya dapat membantu menjawab pertanyaan seputar perpustakaan SMAN 4 Jember dan rekomendasi buku di katalog kami. Silakan tanyakan hal yang berkaitan dengan itu ya! 📚✨"

BUKU YANG DITEMUKAN (Terkait pertanyaan user saat ini):
{$bookList}
PROMPT;
    }
}
