<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AtributJenisSurat extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;
    use Blameable;

    protected $table = 'atribut_jenis_surat';

    protected $fillable = [
        'jenis_surat_id',
        'nama_atribut',
        'tipe_data',
        'opsi_pilihan',
        'is_required',
        'nama_lampiran',
        'minimal_file',
        'is_required_lampiran',
        'urutan',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_required_lampiran' => 'boolean',
        'minimal_file' => 'integer',
        'urutan' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'ATRIBUT JENIS SURAT');
    }

    public function jenisSurat()
    {
        return $this->belongsTo(JenisSurat::class, 'jenis_surat_id');
    }

    public function pengajuanSuratAtribut()
    {
        return $this->hasMany(PengajuanSuratAtribut::class, 'atribut_jenis_surat_id');
    }

    /**
     * Get opsi pilihan as array
     */
    public function getOpsiPilihanArrayAttribute()
    {
        if (empty($this->opsi_pilihan)) {
            return [];
        }

        // Try to decode as JSON first
        $decoded = json_decode($this->opsi_pilihan, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // If not JSON, treat as comma-separated
        return array_map('trim', explode(',', $this->opsi_pilihan));
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

