<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = [
        'user_id', 'class_id', 'member_code', 'qr_token', 'name',
        'type', 'nis_nip', 'phone', 'address', 'photo', 'status',
        'expired_at', 'verified_at', 'verified_by', 'rejection_reason',
        'suspend_reason', 'notes',
        // Extra fields
        'nisn', 'nik', 'tempat_lahir', 'tanggal_lahir',
        'agama', 'jenis_kelamin', 'pangkat_golongan',
    ];

    protected $casts = [
        'expired_at'    => 'date',
        'verified_at'   => 'datetime',
        'tanggal_lahir' => 'date',
    ];

    // Relasi ke User
    public function user()       { return $this->belongsTo(User::class); }
    public function kelas()      { return $this->belongsTo(Kelas::class, 'class_id'); }
    public function verifiedBy() { return $this->belongsTo(User::class, 'verified_by'); }

    // Relasi transaksi
    public function loans()         { return $this->hasMany(Loan::class); }
    public function visits()        { return $this->hasMany(Visit::class); }
    public function notifications() { return $this->hasMany(MemberNotification::class); }

    // E-Book
    public function ebookBookmarks()       { return $this->hasMany(EbookBookmark::class); }
    public function ebookReadingProgress() { return $this->hasMany(EbookReadingProgress::class); }

    // Helper status
    public function isAktif(): bool    { return $this->status === 'aktif'; }
    public function isPending(): bool  { return $this->status === 'pending'; }

    // Scope
    public function scopeAktif($query)   { return $query->where('status', 'aktif'); }
    public function scopePending($query) { return $query->where('status', 'pending'); }

    /**
     * Override toArray to ensure nik falls back to member_code or nis_nip if empty.
     */
    public function toArray()
    {
        $array = parent::toArray();
        if (empty($array['nik'])) {
            $array['nik'] = $this->member_code ?: ($this->nis_nip ?: '');
        }
        return $array;
    }
}
