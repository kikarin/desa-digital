<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanSuratAtribut extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_surat_atribut';

    protected $fillable = [
        'pengajuan_surat_id',
        'atribut_jenis_surat_id',
        'nilai',
        'lampiran_files',
    ];

    protected $casts = [
        'lampiran_files' => 'array',
    ];

    public function pengajuanSurat()
    {
        return $this->belongsTo(PengajuanSurat::class, 'pengajuan_surat_id');
    }

    public function atributJenisSurat()
    {
        return $this->belongsTo(AtributJenisSurat::class, 'atribut_jenis_surat_id');
    }
}

