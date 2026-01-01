<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Rws extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use Blameable;

    protected $table = 'rws';

    protected $fillable = [
        'nomor_rw',
        'desa',
        'kecamatan',
        'kabupaten',
        'boundary',
    ];

    protected $casts = [
        'boundary' => 'array',
    ];

    /**
     * Konfigurasi activity log untuk tracking perubahan data
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'RWS');
    }

    /**
     * Relasi ke user yang membuat data
     */
    public function created_by_user()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by')->select(['id', 'name', 'file']);
    }

    /**
     * Relasi ke user yang update data
     */
    public function updated_by_user()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by')->select(['id', 'name']);
    }
}

