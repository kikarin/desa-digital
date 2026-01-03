<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PengajuanProposal extends Model
{
    use HasFactory, SoftDeletes, Blameable, LogsActivity;

    protected $table = 'pengajuan_proposal';

    protected $fillable = [
        'kategori_proposal_id',
        'resident_id',
        'nomor_telepon_pengaju',
        'nama_kegiatan',
        'deskripsi_kegiatan',
        'usulan_anggaran',
        'file_pendukung',
        'latitude',
        'longitude',
        'nama_lokasi',
        'alamat',
        'thumbnail_foto_banner',
        'tanda_tangan_digital',
        'status',
        'catatan_verifikasi',
        'admin_verifikasi_id',
        'tanggal_diverifikasi',
    ];

    protected $casts = [
        'usulan_anggaran' => 'decimal:2',
        'file_pendukung' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'tanggal_diverifikasi' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Pengajuan Proposal');
    }

    public function kategoriProposal()
    {
        return $this->belongsTo(KategoriProposal::class, 'kategori_proposal_id');
    }

    public function resident()
    {
        return $this->belongsTo(Residents::class, 'resident_id');
    }

    public function adminVerifikasi()
    {
        return $this->belongsTo(User::class, 'admin_verifikasi_id');
    }

    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by_user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'menunggu_verifikasi' => 'Menunggu Verifikasi',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function canBeEdited()
    {
        return in_array($this->status, ['menunggu_verifikasi', 'ditolak']);
    }
}

