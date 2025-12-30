<?php

namespace App\Repositories;

use App\Models\AdminTandaTangan;
use App\Traits\RepositoryTrait;

class AdminTandaTanganRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(AdminTandaTangan $model)
    {
        $this->model = $model;
        $this->with = ['admin'];
    }

    /**
     * Get or create TTD for admin
     */
    public function getOrCreateTtd($adminId, $type)
    {
        return $this->model->firstOrCreate(
            [
                'admin_id' => $adminId,
                'tanda_tangan_type' => $type,
            ],
            [
                'admin_id' => $adminId,
                'tanda_tangan_type' => $type,
            ]
        );
    }

    /**
     * Get TTD by admin and type
     */
    public function getTtdByAdminAndType($adminId, $type)
    {
        return $this->model->where('admin_id', $adminId)
            ->where('tanda_tangan_type', $type)
            ->first();
    }

    /**
     * Get all TTD for admin
     */
    public function getAllTtdByAdmin($adminId)
    {
        return $this->model->where('admin_id', $adminId)->get();
    }
}

