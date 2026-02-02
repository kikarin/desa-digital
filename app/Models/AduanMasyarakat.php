<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AduanMasyarakat extends Model
{
    use HasFactory, SoftDeletes, Blameable, LogsActivity;

    protected $table = 'aduan_masyarakat';

    protected $fillable = [
        'kategori_aduan_id',
        'judul',
        'detail_aduan',
        'latitude',
        'longitude',
        'nama_lokasi',
        'kecamatan_id',
        'desa_id',
        'deskripsi_lokasi',
        'jenis_aduan',
        'alasan_melaporkan',
        'status',
        'rt_verifikasi_id',
        'rt_verifikasi_at',
        'rt_catatan',
        'admin_verifikasi_id',
        'admin_verifikasi_at',
        'admin_catatan',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'rt_verifikasi_at' => 'datetime',
        'admin_verifikasi_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['kategori_aduan_id', 'judul', 'status', 'jenis_aduan'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Aduan Masyarakat');
    }

    public function kategori_aduan()
    {
        return $this->belongsTo(KategoriAduan::class, 'kategori_aduan_id')->withDefault();
    }

    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id')->withDefault();
    }

    public function desa()
    {
        return $this->belongsTo(Desa::class, 'desa_id')->withDefault();
    }

    public function files()
    {
        return $this->hasMany(AduanMasyarakatFile::class, 'aduan_masyarakat_id');
    }

    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by_user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function layanan_darurat()
    {
        return $this->belongsToMany(LayananDarurat::class, 'aduan_masyarakat_layanan_darurat', 'aduan_masyarakat_id', 'layanan_darurat_id');
    }

    public function rt_verifikasi()
    {
        return $this->belongsTo(User::class, 'rt_verifikasi_id');
    }

    public function admin_verifikasi()
    {
        return $this->belongsTo(User::class, 'admin_verifikasi_id');
    }
}

