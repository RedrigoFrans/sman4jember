<?php

namespace App\Services;

use App\Models\FcmToken;
use App\Models\Fine;
use App\Models\FinePayment;
use App\Models\MemberNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FineService
{
    public function __construct(private FcmService $fcm) {}

    /**
     * Bayar denda (bisa sebagian/cicil).
     */
    public function pay(Fine $fine, int $amountPaid, User $receivedBy, ?string $receiptNumber = null): FinePayment
    {
        $isNowPaid = false;

        $payment = DB::transaction(function () use ($fine, $amountPaid, $receivedBy, $receiptNumber, &$isNowPaid) {
            $payment = FinePayment::create([
                'fine_id'        => $fine->id,
                'amount_paid'    => $amountPaid,
                'payment_date'   => today(),
                'receipt_number' => $receiptNumber,
                'received_by'    => $receivedBy->id,
            ]);

            $totalPaid = $fine->payments()->sum('amount_paid');

            if ($totalPaid >= $fine->amount) {
                $fine->update([
                    'status'       => 'lunas',
                    'paid_at'      => now(),
                    'confirmed_by' => $receivedBy->id,
                ]);
                $this->clearFineNotificationIfAllPaid($fine);
                $isNowPaid = true;
            }

            return $payment;
        });

        // ─── Kirim notifikasi lunas ke member (DILUAR transaksi) ───────────
        if ($isNowPaid) {
            $this->sendFineSettledNotification($fine, 'lunas');
        }

        return $payment;
    }

    /**
     * Bebaskan denda (waive).
     */
    public function free(Fine $fine, string $reason, User $freedBy): Fine
    {
        $fine->update([
            'status'       => 'dibebaskan',
            'freed_by'     => $freedBy->id,
            'freed_reason' => $reason,
        ]);

        $this->clearFineNotificationIfAllPaid($fine);

        // ─── Kirim notifikasi dibebaskan ke member ─────────────────────────
        $this->sendFineSettledNotification($fine, 'dibebaskan');

        return $fine->fresh();
    }

    /**
     * Total denda belum lunas untuk member tertentu.
     */
    public function totalUnpaid(int $memberId): int
    {
        return Fine::whereHas('loanItem.loan', fn($q) => $q->where('member_id', $memberId))
            ->where('status', 'belum_lunas')
            ->sum('amount');
    }

    /**
     * Kirim notifikasi 'denda_lunas' ke member saat denda lunas atau dibebaskan.
     */
    private function sendFineSettledNotification(Fine $fine, string $status): void
    {
        $fine->loadMissing('loanItem.loan.member', 'loanItem.copy.book');

        $member    = $fine->loanItem?->loan?->member;
        $bookTitle = $fine->loanItem?->copy?->book?->title ?? 'Buku';
        $loanId    = $fine->loanItem?->loan_id;

        if (!$member) return;

        // Cek apakah semua denda lunas
        $sisaDenda = $this->totalUnpaid($member->id);

        if ($status === 'lunas') {
            $title = '✅ Denda Telah Lunas';
            $body  = "Pembayaran denda buku \"{$bookTitle}\" sebesar Rp" .
                     number_format($fine->amount, 0, ',', '.') .
                     " telah dikonfirmasi. Terima kasih!";
        } else {
            $title = '🎉 Denda Dibebaskan';
            $body  = "Denda buku \"{$bookTitle}\" sebesar Rp" .
                     number_format($fine->amount, 0, ',', '.') .
                     " telah dibebaskan oleh petugas.";
        }

        // 1. Simpan ke DB (in-app notification)
        MemberNotification::create([
            'member_id'  => $member->id,
            'type'       => 'denda_lunas',
            'title'      => $title,
            'body'       => $body,
            'data'       => [
                'fine_id'    => (string) $fine->id,
                'loan_id'    => (string) $loanId,
                'sisa_denda' => (string) $sisaDenda,
            ],
            'is_read'    => false,
            'sent_at'    => now(),
            'created_at' => now(),
        ]);

        // 2. FCM push notification
        $tokens = FcmToken::where('user_id', $member->user_id ?? null)
            ->orWhereHas('user', fn($q) => $q->whereHas('member', fn($q2) => $q2->where('id', $member->id)))
            ->pluck('token')
            ->toArray();

        if (!empty($tokens)) {
            $this->fcm->sendMultiple($tokens, $title, $body, [
                'type'    => 'denda_lunas',
                'fine_id' => (string) $fine->id,
                'loan_id' => (string) $loanId,
            ]);
            Log::info("[FineService] FCM denda_lunas sent to member {$member->id}, tokens: " . count($tokens));
        } else {
            Log::info("[FineService] Member {$member->id} has no FCM token, skipped push.");
        }
    }

    /**
     * Clear the 'denda_belum_lunas' admin notification for a member if they have paid all their fines.
     */
    private function clearFineNotificationIfAllPaid(Fine $fine): void
    {
        $fine->loadMissing('loanItem.loan.member');
        $memberId   = $fine->loanItem?->loan?->member_id;
        $memberName = $fine->loanItem?->loan?->member?->name;

        if ($memberId && $memberName) {
            if ($this->totalUnpaid($memberId) <= 0) {
                \App\Models\AdminNotification::where('type', 'denda_belum_lunas')
                    ->where('message', 'like', "%{$memberName}%")
                    ->update(['is_read' => true]);
            }
        }
    }
}
