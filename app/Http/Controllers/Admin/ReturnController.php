<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookCopy;
use App\Services\ReturnService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReturnController extends Controller
{
    public function __construct(private ReturnService $returnService) {}

    public function index()
    {
        return Inertia::render('Admin/Returns/Scan');
    }

    // Validasi buku yang dikembalikan berdasarkan barcode
    public function check(Request $request)
    {
        $request->validate(['barcode' => 'required|string']);

        $copy = BookCopy::with(['book', 'loanItems' => fn($q) => $q->whereNull('returned_at')->with('loan.member')])
            ->where('barcode', $request->barcode)
            ->orWhere('copy_code', $request->barcode)
            ->first();

        if (!$copy) {
            return response()->json(['found' => false, 'message' => 'Buku tidak ditemukan.'], 404);
        }

        $activeLoanItem = $copy->loanItems->first();

        if (!$activeLoanItem) {
            return response()->json(['found' => true, 'on_loan' => false, 'message' => 'Buku ini tidak sedang dipinjam.']);
        }

        return response()->json([
            'found'      => true,
            'on_loan'    => true,
            'copy'       => $copy,
            'loan_item'  => $activeLoanItem,
            'member'     => $activeLoanItem->loan->member,
            'due_date'   => $activeLoanItem->loan->effectiveDueDate(),
            'is_overdue' => now()->gt($activeLoanItem->loan->effectiveDueDate()),
        ]);
    }

    public function store(Request $request, \App\Services\FcmService $fcm)
    {
        $request->validate([
            'barcode'         => 'required|string',
            'condition_after' => 'required|in:baik,rusak_ringan,rusak_berat,hilang',
        ]);

        $result = $this->returnService->processReturn(
            $request->barcode,
            $request->condition_after,
            $request->user()
        );

        // --- Kirim Push Notification Pengembalian ---
        if ($result['fines']->isEmpty()) {
            try {
                $loanItem = $result['loan_item'];
                // load relasi member & book title
                $loanItem->loadMissing(['loan.member', 'copy.book']);
                $member = $loanItem->loan->member;
                $bookTitle = $loanItem->copy->book->title ?? 'Buku';

                $title = 'Pengembalian Berhasil';
                $body = "Buku \"$bookTitle\" telah berhasil dikembalikan.";

                \App\Models\MemberNotification::create([
                    'member_id'  => $member->id,
                    'type'       => 'pengembalian_berhasil',
                    'title'      => $title,
                    'body'       => $body,
                    'data'       => ['loan_id' => (string) $loanItem->loan_id],
                    'is_read'    => false,
                    'sent_at'    => now(),
                ]);

                $tokens = \App\Models\FcmToken::where('user_id', $member->user_id)
                    ->orWhereHas('user', fn($q) => $q->whereHas('member', fn($q2) => $q2->where('id', $member->id)))
                    ->pluck('token')
                    ->toArray();

                if (!empty($tokens)) {
                    $fcm->sendMultiple($tokens, $title, $body, ['loan_id' => (string) $loanItem->loan_id]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Gagal kirim push notification pengembalian: ' . $e->getMessage());
            }
        }
        // ----------------------------------------------

        return response()->json([
            'success'   => true,
            'loan_item' => $result['loan_item'],
            'fines'     => $result['fines'],
            'message'   => $result['fines']->isEmpty()
                ? 'Pengembalian berhasil. Tidak ada denda.'
                : "Pengembalian berhasil. Denda: Rp " . number_format($result['fines']->sum('amount'), 0, ',', '.'),
        ]);
    }
}
