<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BankSampah extends Model
{
    use HasFactory, SoftDeletes, Blameable, LogsActivity;

    protected $table = 'bank_sampah';

    protected $fillable = [
        'latitude',
        'longitude',
        'nama_lokasi',
        'alamat',
        'title',
        'foto',
        'deskripsi',
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
            ->logOnly(['latitude', 'longitude', 'nama_lokasi', 'alamat', 'title', 'foto', 'deskripsi'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Bank Sampah');
    }

    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by_user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

