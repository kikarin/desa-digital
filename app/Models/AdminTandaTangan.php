<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminTandaTangan extends Model
{
    use HasFactory;

    protected $table = 'admin_tanda_tangan';

    protected $fillable = [
        'admin_id',
        'tanda_tangan_digital',
        'foto_tanda_tangan',
        'tanda_tangan_type',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}

