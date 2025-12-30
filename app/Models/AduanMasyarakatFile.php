<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AduanMasyarakatFile extends Model
{
    use HasFactory;

    protected $table = 'aduan_masyarakat_files';

    protected $fillable = [
        'aduan_masyarakat_id',
        'file_path',
        'file_type',
        'file_name',
    ];

    public function aduan_masyarakat()
    {
        return $this->belongsTo(AduanMasyarakat::class, 'aduan_masyarakat_id');
    }
}

