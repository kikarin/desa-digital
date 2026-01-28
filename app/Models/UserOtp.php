<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOtp extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'expires_at'   => 'datetime',
        'last_sent_at' => 'datetime',
        'is_used'      => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at instanceof Carbon && $this->expires_at->isPast();
    }
}

