<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kecamatan extends Model
{
    use HasFactory;

    protected $table = 'mst_kecamatan';

    protected $fillable = [
        'nama',
        'latitude',
        'longitude',
    ];

    public function desas()
    {
        return $this->hasMany(Desa::class, 'id_kecamatan');
    }
}

