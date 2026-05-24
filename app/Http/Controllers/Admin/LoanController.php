<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Member;
use App\Models\BookCopy;
use App\Services\LoanService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoanController extends Controller
{
    public function __construct(private LoanService $loanService)
    {
    }

    public function index(Request $request)
    {
        $loans = Loan::with(['member', 'items.copy.book', 'createdBy'])
            ->when($request->status, function ($q, $s) {
            if ($s === 'terlambat') {
                $q->where(fn($q2) => $q2->where('status', 'terlambat')
                ->orWhere(fn($q3) => $q3->whereIn('status', ['aktif', 'diperpanjang'])
                ->whereRaw('COALESCE(extended_due_date, due_date) < ?', [now()->toDateString()])));
            }
            else {
                $q->where('status', $s);
            }
        })
            ->when($request->search, fn($q, $s) => $q->whereHas('member', fn($m) => $m->where('name', 'like', "%{$s}%")
        ->orWhere('member_code', 'like', "%{$s}%")))
            ->latest()->paginate(20)->withQueryString();

        return Inertia::render('Admin/Loans/Index', [
            'loans' => $loans,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function riwayat(Request $request)
    {
        $loans = Loan::with(['member', 'items.copy.book', 'createdBy'])
            ->when($request->status, function ($q, $s) {
            if ($s === 'terlambat') {
                $q->where(fn($q2) => $q2->where('status', 'terlambat')
                ->orWhere(fn($q3) => $q3->whereIn('status', ['aktif', 'diperpanjang'])
                ->whereRaw('COALESCE(extended_due_date, due_date) < ?', [now()->toDateString()])));
            }
            else {
                $q->where('status', $s);
            }
        })
            ->when($request->search, fn($q, $s) => $q->whereHas('member', fn($m) => $m->where('name', 'like', "%{$s}%")
        ->orWhere('member_code', 'like', "%{$s}%")))
            ->latest()->paginate(20)->withQueryString();

        return Inertia::render('Admin/History/Index', [
            'loans' => $loans,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Loans/Create');
    }

    // Validasi anggota via member code (untuk scan QR)
    public function validateMember(Request $request)
    {
        $request->validate(['member_code' => 'required|string']);

        $member = Member::with(['user'])->where('member_code', $request->member_code)->first();

        if (!$member) {
            return response()->json(['valid' => false, 'message' => 'Anggota tidak ditemukan.'], 404);
        }

        $validation = $this->loanService->validateMember($member);

        return response()->json([
            'valid' => $validation['valid'],
            'message' => $validation['message'],
            'member' => $member,
            'quota' => $validation['quota_remaining'] ?? null,
        ]);
    }

    // Validasi buku via barcode
    public function validateBook(Request $request)
    {
        $request->validate(['barcode' => 'required|string']);

        $copy = BookCopy::with('book.category')
            ->where('barcode', $request->barcode)
            ->orWhere('copy_code', $request->barcode)
            ->first();

        if (!$copy) {
            return response()->json(['valid' => false, 'message' => 'Buku tidak ditemukan.'], 404);
        }

        if ($copy->status !== 'tersedia') {
            return response()->json(['valid' => false, 'message' => "Buku berstatus '{$copy->status}', tidak tersedia."], 422);
        }

        return response()->json(['valid' => true, 'copy' => $copy]);
    }

    public function store(Request $request, \App\Services\FcmService $fcm)
    {
        $request->validate([
            'member_code' => 'required|string',
            'barcodes' => 'required|array|min:1|max:2',
            'barcodes.*' => 'required|string',
            'loan_type' => 'required|in:pembaca,lomba',
        ]);

        $member = Member::where('member_code', $request->member_code)->firstOrFail();
        $validation = $this->loanService->validateMember($member);

        if (!$validation['valid']) {
            return back()->withErrors(['member_code' => $validation['message']]);
        }

        $loan = $this->loanService->create($member, $request->barcodes, $request->user(), $request->loan_type);

        // --- Kirim Push Notification ---
        try {
            $dueDateFormatted = \Carbon\Carbon::parse($loan->due_date)->locale('id')->translatedFormat('d F Y');
            $title = 'Peminjaman Berhasil';
            $body = "Buku berhasil dipinjam. Harap kembalikan sebelum atau pada tanggal $dueDateFormatted.";
            
            \App\Models\MemberNotification::create([
                'member_id'  => $member->id,
                'type'       => 'peminjaman_berhasil',
                'title'      => $title,
                'body'       => $body,
                'data'       => ['loan_id' => (string) $loan->id],
                'is_read'    => false,
                'sent_at'    => now(),
            ]);

            $tokens = \App\Models\FcmToken::where('user_id', $member->user_id)
                ->orWhereHas('user', fn($q) => $q->whereHas('member', fn($q2) => $q2->where('id', $member->id)))
                ->pluck('token')
                ->toArray();

            if (!empty($tokens)) {
                $fcm->sendMultiple($tokens, $title, $body, ['loan_id' => (string) $loan->id]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gagal kirim push notification peminjaman: ' . $e->getMessage());
        }
        // -------------------------------

        return redirect()->route('history.index')->with('success', 'Peminjaman berhasil dibuat.');
    }

    public function extend(Loan $loan, Request $request)
    {
        try {
            $loan = $this->loanService->extend($loan, $request->user());

            // -------------------------------
            // KIRIM PUSH NOTIFICATION (FCM)
            // -------------------------------
            try {
                $fcm = app(\App\Services\FcmService::class);
                $title = 'Perpanjangan Masa Pinjam';
                
                $newDueDateStr = $loan->extended_due_date 
                    ? \Carbon\Carbon::parse($loan->extended_due_date)->translatedFormat('d F Y')
                    : \Carbon\Carbon::parse($loan->due_date)->translatedFormat('d F Y');
                    
                $body = "Masa peminjaman buku Anda telah diperpanjang. Tenggat waktu pengembalian yang baru adalah $newDueDateStr.";

                // Simpan notifikasi ke database
                \App\Models\MemberNotification::create([
                    'member_id'  => $loan->member_id,
                    'type'       => 'perpanjangan_peminjaman',
                    'title'      => $title,
                    'body'       => $body,
                    'data'       => ['loan_id' => (string) $loan->id],
                    'is_read'    => false,
                    'sent_at'    => now(),
                ]);

                // Ambil token perangkat milik member yang meminjam
                $tokens = \App\Models\FcmToken::where('user_id', $loan->member->user_id)
                    ->pluck('token')
                    ->toArray();

                if (!empty($tokens)) {
                    $fcm->sendMultiple($tokens, $title, $body, ['loan_id' => (string) $loan->id]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Gagal kirim push notification perpanjangan: ' . $e->getMessage());
            }
            // -------------------------------

            return back()->with('success', 'Peminjaman berhasil diperpanjang.');
        }
        catch (\Exception $e) {
            return back()->withErrors(['extend' => $e->getMessage()]);
        }
    }
}