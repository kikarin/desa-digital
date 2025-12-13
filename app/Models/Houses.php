<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Rts;
use App\Models\User;
use App\Models\Families;
use App\Models\Residents;

class Houses extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use Blameable;

    protected $table = 'houses';

    protected $fillable = [
        'rt_id',
        'nomor_rumah',
        'jenis_rumah',
        'keterangan',
        'pemilik_id',
        'nama_pemilik',
        'status_hunian',
        'nama_usaha',
        'nama_pengelola',
        'jenis_usaha',
        'nama_fasilitas',
        'pengelola',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'HOUSES');
    }

    /**
     * Relasi ke Rts
     */
    public function rt()
    {
        return $this->belongsTo(Rts::class, 'rt_id');
    }

    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by')->select(['id', 'name', 'file']);
    }

    public function updated_by_user()
    {
        return $this->belongsTo(User::class, 'updated_by')->select(['id', 'name']);
    }

    public function families()
    {
        return $this->hasMany(Families::class, 'house_id');
    }

    public function pemilik()
    {
        return $this->belongsTo(Residents::class, 'pemilik_id');
    }
}

