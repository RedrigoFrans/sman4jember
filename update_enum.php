<?php
\Illuminate\Support\Facades\DB::statement("ALTER TABLE member_notifications MODIFY COLUMN type ENUM('akun_diaktifkan','akun_ditolak','reminder_pengembalian','terlambat_pengembalian','denda_baru','denda_lunas','reservasi_tersedia','reservasi_kadaluarsa','perpanjangan_berhasil','info','peminjaman_berhasil','pengembalian_berhasil') NOT NULL");
echo "Done";
