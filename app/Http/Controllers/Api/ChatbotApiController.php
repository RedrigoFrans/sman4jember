<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatbotApiController extends Controller
{
    /**
     * GET /chatbot/conversations
     * Daftar semua percakapan milik user yang login.
     */
    public function conversations(Request $request)
    {
        $conversations = ChatConversation::where('user_id', $request->user()->id)
            ->with('latestMessage')
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn($c) => [
                'id'             => $c->id,
                'title'          => $c->title,
                'last_message'   => $c->latestMessage?->message ? Str::limit($c->latestMessage->message, 60) : null,
                'last_role'      => $c->latestMessage?->role,
                'updated_at'     => $c->updated_at->toISOString(),
                'message_count'  => $c->messages_count,
            ]);

        return response()->json(['data' => $conversations]);
    }

    /**
     * POST /chatbot/conversations
     * Buat percakapan baru.
     */
    public function createConversation(Request $request)
    {
        $conversation = ChatConversation::create([
            'user_id' => $request->user()->id,
            'title'   => 'Percakapan Baru',
        ]);

        return response()->json([
            'data' => [
                'id'         => $conversation->id,
                'title'      => $conversation->title,
                'created_at' => $conversation->created_at->toISOString(),
            ]
        ], 201);
    }

    /**
     * DELETE /chatbot/conversations/{id}
     * Hapus percakapan beserta semua pesannya.
     */
    public function deleteConversation(Request $request, $id)
    {
        $conversation = ChatConversation::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $conversation->delete(); // cascade delete messages

        return response()->json(['message' => 'Percakapan berhasil dihapus.']);
    }

    /**
     * GET /chatbot/conversations/{id}/messages
     * Ambil semua pesan dalam percakapan.
     */
    public function messages(Request $request, $id)
    {
        $conversation = ChatConversation::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn($m) => [
                'id'         => $m->id,
                'role'       => $m->role,
                'message'    => $m->message,
                'books'      => $m->books,
                'created_at' => $m->created_at->toISOString(),
            ]);

        return response()->json([
            'conversation' => [
                'id'    => $conversation->id,
                'title' => $conversation->title,
            ],
            'data' => $messages,
        ]);
    }

    /**
     * POST /chatbot/send
     * Kirim pesan dan dapatkan jawaban AI.
     */
    public function send(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|integer|exists:chat_conversations,id',
            'message'         => 'required|string|max:1000',
        ]);

        $user = $request->user();
        $conversation = ChatConversation::where('user_id', $user->id)
            ->findOrFail($request->conversation_id);

        // 1. Simpan pesan user
        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'message'         => $request->message,
        ]);

        // 2. Retrieve: Cari buku relevan dari database
        $relevantBooks = $this->retrieveBooks($request->message);

        // 3. Load riwayat chat (5 pesan terakhir untuk konteks)
        $chatHistory = $conversation->messages()
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->sortBy('created_at')
            ->values()
            ->map(fn($m) => [
                'role'    => $m->role,
                'message' => $m->message,
            ])
            ->toArray();

        // Hapus pesan user terbaru dari history (sudah akan dikirim sebagai pesan utama)
        if (count($chatHistory) > 0) {
            array_pop($chatHistory);
        }

        // 4. Format konteks buku
        $bookContext = $relevantBooks->map(fn($book) => [
            'id'          => $book->id,
            'title'       => $book->title,
            'author'      => $book->author,
            'category'    => $book->category?->name ?? 'Uncategorized',
            'loans'       => $book->total_loans ?? 0,
            'stock'       => $book->availableCopies()->count(),
            'description' => Str::limit($book->description, 70),
        ])->toArray();

        // 5. Generate: Kirim ke Gemini
        $totalBooksInDB = Book::count();
        $categoryStats = \App\Models\Category::withCount('books')->get()
            ->map(fn($c) => "- {$c->name}: {$c->books_count} buku")
            ->implode("\n");
        
        $gemini = new GeminiService();
        $result = $gemini->chat($request->message, $bookContext, $chatHistory, $totalBooksInDB, $categoryStats);

        // Jika Gemini gagal, return error yang jelas ke mobile
        if (!$result['success']) {
            \Illuminate\Support\Facades\Log::error('ChatbotApiController: Gemini gagal', [
                'user_id'         => $user->id,
                'conversation_id' => $conversation->id,
                'reply'           => $result['reply'],
            ]);

            return response()->json([
                'error'   => 'Layanan AI tidak tersedia',
                'message' => $result['reply'],
            ], 503);
        }

        // 6. Prepare books data untuk response
        $recommendedBooks = $relevantBooks->take(5)->map(fn($book) => [
            'id'          => $book->id,
            'title'       => $book->title,
            'author'      => $book->author,
            'cover_image' => $book->cover_image 
                ? (str_starts_with($book->cover_image, 'http') ? $book->cover_image : url(Storage::url($book->cover_image))) 
                : null,
            'category'    => $book->category?->name ?? 'Uncategorized',
        ])->toArray();

        // 7. Simpan jawaban bot
        $botMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'assistant',
            'message'         => $result['reply'],
            'books'           => !empty($recommendedBooks) ? $recommendedBooks : null,
        ]);

        // 8. Auto-update title dari pesan pertama user
        if ($conversation->title === 'Percakapan Baru') {
            $conversation->update([
                'title' => Str::limit($request->message, 50),
            ]);
        }

        // Touch updated_at
        $conversation->touch();

        return response()->json([
            'reply'        => $result['reply'],
            'books'        => $recommendedBooks,
            'conversation' => [
                'id'    => $conversation->id,
                'title' => $conversation->fresh()->title,
            ],
        ]);
    }

    /**
     * Retrieve buku relevan dari database berdasarkan pesan user.
     */
    protected function retrieveBooks(string $message): \Illuminate\Support\Collection
    {
        $keywords = $this->extractKeywords($message);
        $lowerMessage = strtolower($message);
        
        $matchedCategoryId = null;
        if (str_contains($lowerMessage, 'non fiksi')) {
            $matchedCategoryId = \App\Models\Category::where('name', 'Non Fiksi')->value('id');
        } elseif (str_contains($lowerMessage, 'fiksi')) {
            $matchedCategoryId = \App\Models\Category::where('name', 'Fiksi')->value('id');
        }

        // Hapus kata bising agar tidak mengacaukan pencarian judul
        $filteredKeywords = array_filter($keywords, fn($kw) => !in_array($kw, ['non', 'fiksi', 'jumlah', 'berapa', 'total']));
        $filteredKeywords = array_values($filteredKeywords);

        $buildQuery = function ($useKeywords) use ($matchedCategoryId, $filteredKeywords) {
            $q = Book::with('category');
            if ($matchedCategoryId) {
                $q->where('category_id', $matchedCategoryId);
            }
            if ($useKeywords && !empty($filteredKeywords)) {
                $q->where(function ($subQ) use ($filteredKeywords) {
                    foreach ($filteredKeywords as $keyword) {
                        $subQ->orWhere('title', 'LIKE', "%{$keyword}%")
                             ->orWhere('author', 'LIKE', "%{$keyword}%")
                             ->orWhere('description', 'LIKE', "%{$keyword}%")
                             ->orWhere('publisher', 'LIKE', "%{$keyword}%");
                    }
                });
            }
            return $q->orderByDesc('total_loans')->take(20)->get();
        };

        $results = $buildQuery(true);

        // Jika pencarian text+kategori gagal (hasil=0), tapi kategorinya valid
        // Hapus filter text dan ambil langsung 20 buku pop dari kategori tersebut
        if ($results->isEmpty() && $matchedCategoryId) {
            $results = $buildQuery(false);
        }
        
        // Terakhir: Jika database ini sama sekali tidak ada yang nyantol
        if ($results->isEmpty()) {
             return Book::with('category')->orderByDesc('total_loans')->take(20)->get();
        }

        return $results;
    }

    /**
     * Ekstrak keyword yang bermakna dari pesan user.
     */
    protected function extractKeywords(string $message): array
    {
        // Hapus stop words Bahasa Indonesia
        $stopWords = [
            'saya', 'ingin', 'mau', 'cari', 'carikan', 'tolong', 'bisa', 'ada',
            'yang', 'dan', 'atau', 'untuk', 'di', 'ke', 'dari', 'dengan',
            'tentang', 'mengenai', 'seputar', 'buku', 'rekomendasi', 'rekomendasikan',
            'apa', 'bagaimana', 'apakah', 'berikan', 'kasih', 'dong', 'kah',
            'tidak', 'belum', 'sudah', 'akan', 'sedang', 'telah', 'ini', 'itu',
            'sangat', 'sekali', 'lebih', 'paling', 'juga', 'lagi', 'hanya',
            'seperti', 'agar', 'supaya', 'karena', 'kalau', 'jika', 'maka',
            'ya', 'hai', 'halo', 'hi', 'hey', 'terima', 'kasih', 'makasih',
            'tampilkan', 'kategori', 'genre', 'jenis', 'judul', 'buat', 'coba'
        ];

        $words = preg_split('/\s+/', mb_strtolower(trim($message)));
        $keywords = array_filter($words, function ($word) use ($stopWords) {
            return mb_strlen($word) > 2 && !in_array($word, $stopWords);
        });

        return array_values($keywords);
    }
}
