<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\User;

class AssistanceRecipient extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use Blameable;

    protected $table = 'assistance_recipients';

    protected $fillable = [
        'assistance_program_id',
        'target_type',
        'family_id',
        'resident_id',
        'kepala_keluarga_id',
        'penerima_lapangan_id',
        'status',
        'tanggal_penyaluran',
        'catatan',
    ];

    protected $casts = [
        'tanggal_penyaluran' => 'date',
    ];

    /**
     * Konfigurasi activity log untuk tracking perubahan data
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'ASSISTANCE_RECIPIENT');
    }

    /**
     * Relasi ke AssistanceProgram
     */
    public function program()
    {
        return $this->belongsTo(AssistanceProgram::class, 'assistance_program_id');
    }

    /**
     * Relasi ke Family (jika target_type = KELUARGA)
     */
    public function family()
    {
        return $this->belongsTo(Families::class, 'family_id');
    }

    /**
     * Relasi ke Resident (jika target_type = INDIVIDU)
     */
    public function resident()
    {
        return $this->belongsTo(Residents::class, 'resident_id');
    }

    /**
     * Relasi ke Kepala Keluarga
     */
    public function kepala_keluarga()
    {
        return $this->belongsTo(Residents::class, 'kepala_keluarga_id');
    }

    /**
     * Relasi ke Penerima Lapangan (perwakilan)
     */
    public function penerima_lapangan()
    {
        return $this->belongsTo(Residents::class, 'penerima_lapangan_id');
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

