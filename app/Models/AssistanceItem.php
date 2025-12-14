<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\User;

class AssistanceItem extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use Blameable;

    protected $table = 'assistance_items';

    protected $fillable = [
        'nama_item',
        'tipe',
        'satuan',
    ];

    /**
     * Konfigurasi activity log untuk tracking perubahan data
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'ASSISTANCE_ITEM');
    }

    /**
     * Relasi ke user yang membuat data
     */
    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by')->select(['id', 'name', 'file']);
    }

    /**
     * Relasi ke user yang update data
     */
    public function updated_by_user()
    {
        return $this->belongsTo(User::class, 'updated_by')->select(['id', 'name']);
    }
}

