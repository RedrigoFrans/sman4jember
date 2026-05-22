<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // Relasi
    public function member()
    {
        return $this->hasOne(Member::class);
    }

    public function fcmTokens()
    {
        return $this->hasMany(FcmToken::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    // Helper
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    public function isPetugas(): bool
    {
        return $this->role === 'petugas';
    }
    public function isAnggota(): bool
    {
        return $this->role === 'anggota';
    }
}
