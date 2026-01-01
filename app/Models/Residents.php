<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Families;
use App\Models\ResidentStatus;
use App\Models\User;

class Residents extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use Blameable;

    protected $table = 'residents';

    protected $fillable = [
        'family_id',
        'nik',
        'nama',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'status_id',
        'status_note',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'RESIDENTS');
    }

    public function family()
    {
        return $this->belongsTo(Families::class, 'family_id');
    }

    public function status()
    {
        return $this->belongsTo(ResidentStatus::class, 'status_id');
    }

    public function resident_moves()
    {
        return $this->hasMany(ResidentMoves::class, 'resident_id');
    }

    public function resident_deaths()
    {
        return $this->hasMany(ResidentDeaths::class, 'resident_id');
    }

    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by')->select(['id', 'name', 'file']);
    }

    public function updated_by_user()
    {
        return $this->belongsTo(User::class, 'updated_by')->select(['id', 'name']);
    }

    public function user()
    {
        return $this->hasOne(User::class, 'resident_id');
    }
}

