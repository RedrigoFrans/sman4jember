<?php

namespace App\Services;

use App\Models\BookCopy;
use App\Models\FcmToken;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\MemberNotification;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReturnService
{
    public function __construct(private FcmService $fcm) {}

    /**
     * Proses pengembalian eksemplar buku berdasarkan barcode.
     * Returns ['loan_item' => LoanItem, 'fines' => Collection]
     */
    public function processReturn(string $barcode, string $conditionAfter, User $returnedBy): array
    {
        $copy = BookCopy::where('barcode', $barcode)
            ->orWhere('copy_code', $barcode)
            ->firstOrFail();

        $loanItem = LoanItem::with(['loan.member'])
            ->where('copy_id', $copy->id)
            ->whereNull('returned_at')
            ->latest()
            ->firstOrFail();

        $fines = DB::transaction(function () use ($loanItem, $copy, $conditionAfter, $returnedBy) {
            $fines = collect();

            // Tandai sebagai dikembalikan
            $loanItem->update([
                'returned_at'    => now(),
                'condition_after'=> $conditionAfter,
                'returned_by'    => $returnedBy->id,
            ]);

            // Hitung keterlambatan
            $effectiveDue = $loanItem->loan->effectiveDueDate();
            $daysLate     = (int) now()->startOfDay()->diffInDays($effectiveDue->startOfDay(), false) * -1;

            if ($daysLate > 0) {
                $dendaPerHari = Setting::get('denda_per_hari', 1000);
                $fine = Fine::create([
                    'loan_item_id' => $loanItem->id,
                    'fine_type'    => 'keterlambatan',
                    'days_late'    => $daysLate,
                    'amount'       => $daysLate * $dendaPerHari,
                    'status'       => 'belum_lunas',
                ]);
                $fines->push($fine);
            }

            // Hitung denda kerusakan/kehilangan
            if (in_array($conditionAfter, ['rusak_ringan', 'rusak_berat'])) {
                $key    = $conditionAfter === 'rusak_ringan' ? 'denda_rusak_ringan' : 'denda_rusak_berat';
                $amount = Setting::get($key);
                $fine = Fine::create([
                    'loan_item_id' => $loanItem->id,
                    'fine_type'    => 'kerusakan',
                    'amount'       => $amount,
                    'status'       => 'belum_lunas',
                ]);
                $fines->push($fine);
                $copy->update(['condition' => $conditionAfter]);
            }

            if ($conditionAfter === 'hilang') {
                $amount = Setting::get('denda_hilang', 50000);
                $fine = Fine::create([
                    'loan_item_id' => $loanItem->id,
                    'fine_type'    => 'kehilangan',
                    'amount'       => $amount,
                    'status'       => 'belum_lunas',
                ]);
                $fines->push($fine);
                $copy->update(['condition' => 'hilang', 'status' => 'tidak_aktif']);
            } else {
                $copy->update(['status' => 'tersedia']);
            }

            // Cek apakah semua item di loan ini sudah dikembalikan
            $allReturned = $loanItem->loan->items()->whereNull('returned_at')->doesntExist();
            if ($allReturned) {
                $loanItem->loan->update(['status' => 'selesai']);
            }

            // Notifikasi untuk admin/petugas jika ada denda baru
            if ($fines->isNotEmpty()) {
                $totalFine  = $fines->sum('amount');
                $memberName = $loanItem->loan->member->name ?? 'Unknown';
                \App\Models\AdminNotification::create([
                    'type'    => 'denda_belum_lunas',
                    'title'   => 'Denda Belum Lunas',
                    'message' => "Terdapat denda baru sebesar Rp" . number_format($totalFine, 0, ',', '.') . " atas nama {$memberName} yang belum dibayar.",
                    'url'     => route('fines.index'),
                ]);
            }

            return $fines;
        });

        // ─── Kirim notifikasi ke member (DILUAR transaksi) ───────────────────
        if ($fines->isNotEmpty()) {
            $member     = $loanItem->loan->member ?? null;
            $totalFine  = $fines->sum('amount');
            $bookTitle  = $loanItem->copy->book->title ?? 'Buku';

            // Rincian jenis denda untuk pesan
            $fineTypes = $fines->map(fn($f) => match ($f->fine_type) {
                'keterlambatan' => "keterlambatan {$f->days_late} hari",
                'kerusakan'     => 'kerusakan buku',
                'kehilangan'    => 'kehilangan buku',
                default         => $f->fine_type,
            })->implode(', ');

            $title = '⚠️ Denda Pengembalian Buku';
            $body  = "Buku \"{$bookTitle}\" telah dikembalikan dengan denda Rp" .
                     number_format($totalFine, 0, ',', '.') .
                     " ({$fineTypes}). Segera lunasi dendamu.";

            if ($member) {
                // 1. Simpan ke database (tampil di notifikasi in-app)
                MemberNotification::create([
                    'member_id'  => $member->id,
                    'type'       => 'denda_baru',
                    'title'      => $title,
                    'body'       => $body,
                    'data'       => [
                        'fine_ids'   => $fines->pluck('id')->map(fn($id) => (string) $id)->toArray(),
                        'total'      => (string) $totalFine,
                        'loan_id'    => (string) $loanItem->loan_id,
                    ],
                    'is_read'    => false,
                    'sent_at'    => now(),
                    'created_at' => now(),
                ]);

                // 2. Kirim FCM push notification ke device member
                $tokens = FcmToken::where('user_id', $member->user_id ?? null)
                    ->orWhereHas('user', fn($q) => $q->whereHas('member', fn($q2) => $q2->where('id', $member->id)))
                    ->pluck('token')
                    ->toArray();

                if (!empty($tokens)) {
                    $this->fcm->sendMultiple($tokens, $title, $body, [
                        'type'    => 'denda_baru',
                        'loan_id' => (string) $loanItem->loan_id,
                        'total'   => (string) $totalFine,
                    ]);
                    Log::info("[ReturnService] FCM denda_baru sent to member {$member->id}, tokens: " . count($tokens));
                } else {
                    Log::info("[ReturnService] Member {$member->id} has no FCM token, skipped push.");
                }
            }
        } else {
            // Pengembalian tanpa denda (Pengembalian Berhasil)
            $member = $loanItem->loan->member ?? null;
            $bookTitle = $loanItem->copy->book->title ?? 'Buku';
            $title = '✅ Pengembalian Berhasil';
            $body = "Buku \"{$bookTitle}\" telah berhasil dikembalikan tepat waktu dalam kondisi baik. Terima kasih!";

            if ($member) {
                // 1. Simpan ke database (in-app)
                MemberNotification::create([
                    'member_id'  => $member->id,
                    'type'       => 'pengembalian_berhasil',
                    'title'      => $title,
                    'body'       => $body,
                    'data'       => [
                        'loan_id'    => (string) $loanItem->loan_id,
                    ],
                    'is_read'    => false,
                    'sent_at'    => now(),
                    'created_at' => now(),
                ]);

                // 2. Kirim FCM push notification
                $tokens = FcmToken::where('user_id', $member->user_id ?? null)
                    ->orWhereHas('user', fn($q) => $q->whereHas('member', fn($q2) => $q2->where('id', $member->id)))
                    ->pluck('token')
                    ->toArray();

                if (!empty($tokens)) {
                    $this->fcm->sendMultiple($tokens, $title, $body, [
                        'type'    => 'pengembalian_berhasil',
                        'loan_id' => (string) $loanItem->loan_id,
                    ]);
                    Log::info("[ReturnService] FCM pengembalian_berhasil sent to member {$member->id}, tokens: " . count($tokens));
                } else {
                    Log::info("[ReturnService] Member {$member->id} has no FCM token, skipped push.");
                }
            }
        }

        return ['loan_item' => $loanItem->fresh(), 'fines' => $fines];
    }
}
