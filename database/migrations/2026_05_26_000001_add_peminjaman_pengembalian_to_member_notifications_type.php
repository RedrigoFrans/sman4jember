<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modify ENUM to add peminjaman_berhasil and pengembalian_berhasil
        DB::statement("ALTER TABLE member_notifications MODIFY COLUMN type ENUM(
            'akun_diaktifkan',
            'akun_ditolak',
            'reminder_pengembalian',
            'terlambat_pengembalian',
            'denda_baru',
            'denda_lunas',
            'reservasi_tersedia',
            'reservasi_kadaluarsa',
            'perpanjangan_berhasil',
            'peminjaman_berhasil',
            'pengembalian_berhasil',
            'info'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE member_notifications MODIFY COLUMN type ENUM(
            'akun_diaktifkan',
            'akun_ditolak',
            'reminder_pengembalian',
            'terlambat_pengembalian',
            'denda_baru',
            'denda_lunas',
            'reservasi_tersedia',
            'reservasi_kadaluarsa',
            'perpanjangan_berhasil',
            'info'
        ) NOT NULL");
    }
};
