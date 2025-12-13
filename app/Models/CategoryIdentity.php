<?php

namespace App\Models;

use App\Blameable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\Storage;

class CategoryIdentity extends Model
{
    use HasFactory;
    use Blameable;
    use SoftDeletes;
    use LogsActivity;
    protected $table = 'category_identity';

    protected $fillable = [
        'name',
        'description',
        'file',
        'sequence',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*'])->logOnlyDirty()->setDescriptionForEvent(fn (string $eventName) => 'Category Identity');
    }

    public function identity()
    {
        return $this->hasMany(Identity::class, 'category_identity_id')->orderBy('sequence', 'asc');
    }

    public function getFileFotoAttribute()
    {
        $file_path = 'category-identity/photo/' . $this->file;
        $result    = asset('assets/img/no-image.jpeg');
        if ($this->file != '' and Storage::disk('public')->exists($file_path)) {
            $result = url(Storage::url($file_path));
        }
        return $result;
    }
}
