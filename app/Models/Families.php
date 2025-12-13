<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Houses;
use App\Models\User;
use App\Models\Residents;

class Families extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use Blameable;

    protected $table = 'families';

    protected $fillable = [
        'house_id',
        'no_kk',
        'kepala_keluarga_id',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'FAMILIES');
    }

    public function house()
    {
        return $this->belongsTo(Houses::class, 'house_id');
    }

    public function residents()
    {
        return $this->hasMany(Residents::class, 'family_id');
    }

    public function kepala_keluarga()
    {
        return $this->belongsTo(Residents::class, 'kepala_keluarga_id');
    }

    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by')->select(['id', 'name', 'file']);
    }

    public function updated_by_user()
    {
        return $this->belongsTo(User::class, 'updated_by')->select(['id', 'name']);
    }
}

