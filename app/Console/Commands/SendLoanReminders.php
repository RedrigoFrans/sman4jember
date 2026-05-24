<?php

namespace App\Console\Commands;

use App\Models\FcmToken;
use App\Models\LoanItem;
use App\Models\MemberNotification;
use App\Services\FcmService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendLoanReminders extends Command
{
    protected $signature   = 'loans:send-reminders';
    protected $description = 'Kirim push notification reminder pengembalian buku';

    public function handle(FcmService $fcm): void
    {
        $today = Carbon::today();
        $this->info("Today: {$today->toDateString()}"); // DEBUG

        // Ambil semua item pinjaman yang belum dikembalikan
        $activeItems = LoanItem::with(['loan.member.user', 'copy.book'])
            ->whereNull('returned_at')
            ->whereHas('loan', fn($q) => $q->whereIn('status', ['aktif', 'diperpanjang', 'terlambat']))
            ->get();

        $this->info("Checking {$activeItems->count()} active loan items...");

        $dueTodayCount = 0;
        $overdueCount = 0;

        foreach ($activeItems as $item) {
            $dueDate = $item->loan?->effectiveDueDate();

            // DEBUG: tampilkan info dasar tiap item
            $this->line("---");
            $this->line("  Item #{$item->id} | loan_id: {$item->loan_id}");
            $this->line("  due_date raw    : " . ($item->loan?->due_date ?? 'NULL'));
            $this->line("  extended_due    : " . ($item->loan?->extended_due_date ?? 'NULL'));
            $this->line("  effectiveDueDate: " . ($dueDate ?? 'NULL'));
            $this->line("  loan status     : " . ($item->loan?->status ?? 'NULL'));

            if (!$dueDate) {
                $this->warn("  ⚠ dueDate null, skip");
                continue;
            }

            $dueDate  = Carbon::parse($dueDate)->startOfDay();
            $diff     = (int) $today->diffInDays($dueDate, false); // negatif = sudah lewat
            $member   = $item->loan->member;
            $bookTitle = $item->copy?->book?->title ?? 'Buku';

            $this->line("  diff (hari)     : {$diff}");
            $this->line("  member_id       : " . ($member?->id ?? 'NULL'));
            $this->line("  member->user_id : " . ($member?->user_id ?? 'NULL'));
            $this->line("  book            : {$bookTitle}");

            if (!$member) {
                $this->warn("  ⚠ Member null, skip");
                continue;
            }

            if ($diff === 1) {
                $this->info("  → Kondisi: H-1, akan kirim reminder");
                $this->sendNotification(
                    $fcm, $member, $item, 'reminder_pengembalian',
                    '⏰ Pengingat Pengembalian',
                    "\"$bookTitle\" harus dikembalikan besok. Jangan sampai terlambat!",
                    ['loan_id' => (string) $item->loan_id, 'days_left' => '1']
                );
            } elseif ($diff === 0) {
                $dueTodayCount++;
                $this->info("  → Kondisi: H-0, akan kirim reminder");
                $this->sendNotification(
                    $fcm, $member, $item, 'reminder_pengembalian',
                    '📚 Hari Pengembalian',
                    "\"$bookTitle\" harus dikembalikan hari ini!",
                    ['loan_id' => (string) $item->loan_id, 'days_left' => '0']
                );
            } elseif ($diff < 0) {
                $overdueCount++;
                $daysLate = abs($diff);
                $this->info("  → Kondisi: Terlambat {$daysLate} hari, akan kirim peringatan");
                $this->sendNotification(
                    $fcm, $member, $item, 'terlambat_pengembalian',
                    '⚠️ Buku Terlambat Dikembalikan',
                    "\"$bookTitle\" sudah terlambat $daysLate hari. Segera kembalikan!",
                    ['loan_id' => (string) $item->loan_id, 'days_late' => (string) $daysLate]
                );
            } else {
                $this->warn("  ⚠ diff={$diff}, tidak masuk kondisi apapun (H-" . $diff . "), skip");
            }
        }

        $this->line("---");

        // --- Kirim Notifikasi Rekap ke Admin ---
        if ($dueTodayCount > 0 || $overdueCount > 0) {
            $msgParts = [];
            if ($dueTodayCount > 0) $msgParts[] = "$dueTodayCount buku jatuh tempo hari ini";
            if ($overdueCount > 0) $msgParts[] = "$overdueCount buku telah terlambat";
            
            \App\Models\AdminNotification::create([
                'type'    => 'peringatan_jatuh_tempo',
                'title'   => 'Peringatan Jatuh Tempo',
                'message' => 'Terdapat ' . implode(' dan ', $msgParts) . '. Silakan cek menu peminjaman.',
                'url'     => route('loans.index'),
            ]);
        }

        $this->info('Done sending loan reminders.');
    }

    private function sendNotification(
        FcmService $fcm,
        $member,
        LoanItem $item,
        string $type,
        string $title,
        string $body,
        array $data = []
    ): void {
        // DEBUG: cek alreadySent
        $alreadySent = MemberNotification::where('member_id', $member->id)
            ->where('type', $type)
            ->whereJsonContains('data->loan_id', $data['loan_id'] ?? '')
            ->whereDate('created_at', today())
            ->exists();

        $this->line("  [sendNotif] alreadySent={$alreadySent} | member={$member->id} | type={$type} | loan_id=" . ($data['loan_id'] ?? '-'));

        if ($alreadySent) {
            $this->warn("  ⚠ Notifikasi sudah dikirim hari ini, skip");
            return;
        }

        // Simpan notifikasi ke database
        MemberNotification::create([
            'member_id'  => $member->id,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'data'       => $data,
            'is_read'    => false,
            'sent_at'    => now(),
            'created_at' => now(),
        ]);

        // DEBUG: cek FCM tokens
        $tokens = FcmToken::where('user_id', $member->user_id ?? null)
            ->orWhereHas('user', fn($q) => $q->whereHas('member', fn($q2) => $q2->where('id', $member->id)))
            ->pluck('token')
            ->toArray();

        $this->line("  [sendNotif] FCM tokens ditemukan: " . count($tokens));

        if (!empty($tokens)) {
            $fcm->sendMultiple($tokens, $title, $body, $data);
            $this->info("  ✓ FCM dikirim ke " . count($tokens) . " token");
        } else {
            $this->warn("  ⚠ Tidak ada FCM token, push notification tidak dikirim");
        }

        $this->line("  → [{$type}] Saved & sent to member #{$member->id}: $title");
    }
}