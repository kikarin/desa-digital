<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PengajuanSurat extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use Blameable;

    protected $table = 'pengajuan_surat';

    protected $fillable = [
        'jenis_surat_id',
        'resident_id',
        'tanggal_surat',
        'status',
        'nomor_surat',
        'tanggal_disetujui',
        'alasan_penolakan',
        'admin_verifikasi_id',
        'tanda_tangan_digital',
        'foto_tanda_tangan',
        'tanda_tangan_type',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
        'tanggal_disetujui' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'PENGAJUAN SURAT');
    }

    public function jenisSurat()
    {
        return $this->belongsTo(JenisSurat::class, 'jenis_surat_id');
    }

    public function resident()
    {
        return $this->belongsTo(Residents::class, 'resident_id');
    }

    public function adminVerifikasi()
    {
        return $this->belongsTo(User::class, 'admin_verifikasi_id');
    }

    public function atribut()
    {
        return $this->hasMany(PengajuanSuratAtribut::class, 'pengajuan_surat_id');
    }

    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by')->select(['id', 'name', 'file']);
    }

    public function updated_by_user()
    {
        return $this->belongsTo(User::class, 'updated_by')->select(['id', 'name']);
    }

    /**
     * Check if pengajuan can be edited
     */
    public function canBeEdited()
    {
        return in_array($this->status, ['menunggu', 'ditolak', 'diperbaiki']);
    }
}

