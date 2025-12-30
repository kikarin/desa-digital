<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LayananDarurat extends Model
{
    use HasFactory, SoftDeletes, Blameable, LogsActivity;

    protected $table = 'layanan_darurat';

    protected $fillable = [
        'kategori',
        'latitude',
        'longitude',
        'title',
        'alamat',
        'nomor_whatsapp',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['kategori', 'latitude', 'longitude', 'title', 'alamat', 'nomor_whatsapp'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Layanan Darurat');
    }

    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by_user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getKategoriLabelAttribute()
    {
        $labels = [
            'polsek' => 'Polsek',
            'puskesmas' => 'Puskesmas',
            'pemadam_kebakaran' => 'Pemadam Kebakaran',
            'rumah_sakit' => 'Rumah Sakit',
        ];

        return $labels[$this->kategori] ?? $this->kategori;
    }
}

