<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BeritaPengumuman extends Model
{
    use HasFactory, SoftDeletes, Blameable, LogsActivity;

    protected $table = 'berita_pengumuman';

    protected $fillable = [
        'tipe',
        'title',
        'foto',
        'tanggal',
        'deskripsi',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tipe', 'title', 'foto', 'tanggal', 'deskripsi'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Berita Pengumuman');
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

