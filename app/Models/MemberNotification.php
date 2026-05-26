<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberNotification extends Model
{
    protected $table = 'member_notifications';
    public const UPDATED_AT = null;

    protected $fillable = [
        'member_id', 'type', 'title', 'body', 'data',
        'is_read', 'sent_at', 'read_at', 'created_at',
    ];

    protected $casts = [
        'data'       => 'array',
        'is_read'    => 'boolean',
        'sent_at'    => 'datetime',
        'read_at'    => 'datetime',
        'created_at' => 'datetime',
    ];

    public function member() { return $this->belongsTo(Member::class); }

    public function scopeUnread($query) { return $query->where('is_read', false); }
}
