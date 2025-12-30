<?php

namespace App\Repositories;

use App\Models\UsersRole;
use App\Traits\RepositoryTrait;

class UsersRoleRepository
{
    use RepositoryTrait;
    protected $model;

    public function __construct(UsersRole $model)
    {
        $this->model = $model;
    }

    /**
     * Set roles untuk user
     *
     * @param int $userId
     * @param array $roleIds
     * @param array $roleData Array dengan key role_id dan value ['rw_id' => ?, 'rt_id' => ?]
     * @return void
     */
    public function setRole($userId, $roleIds, $roleData = [])
    {
        // Hapus role lama
        $this->model->where('users_id', $userId)->delete();

        // Insert role baru
        if (!empty($roleIds) && is_array($roleIds)) {
            $data = [];
            foreach ($roleIds as $roleId) {
                $roleInfo = [
                    'users_id'   => $userId,
                    'role_id'    => $roleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                // Tambahkan rw_id atau rt_id jika ada di roleData
                if (isset($roleData[$roleId])) {
                    if (isset($roleData[$roleId]['rw_id'])) {
                        $roleInfo['rw_id'] = $roleData[$roleId]['rw_id'];
                    }
                    if (isset($roleData[$roleId]['rt_id'])) {
                        $roleInfo['rt_id'] = $roleData[$roleId]['rt_id'];
                    }
                }
                
                $data[] = $roleInfo;
            }

            $this->model->insert($data);
        }
    }

    /**
     * Get roles untuk user tertentu
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserRoles($userId)
    {
        return $this->model->with('role')
            ->where('users_id', $userId)
            ->get();
    }

    /**
     * Check apakah user memiliki role tertentu
     *
     * @param int $userId
     * @param int|array $roleId
     * @return bool
     */
    public function hasRole($userId, $roleId)
    {
        $query = $this->model->where('users_id', $userId);

        if (is_array($roleId)) {
            return $query->whereIn('role_id', $roleId)->exists();
        }

        return $query->where('role_id', $roleId)->exists();
    }

    /**
     * Add role ke user (tanpa menghapus role lama)
     *
     * @param int $userId
     * @param int|array $roleId
     * @return void
     */
    public function addRole($userId, $roleId)
    {
        $roleIds = is_array($roleId) ? $roleId : [$roleId];

        foreach ($roleIds as $role) {
            // Check apakah sudah ada
            $exists = $this->model->where('users_id', $userId)
                ->where('role_id', $role)
                ->exists();

            if (!$exists) {
                $this->model->create([
                    'users_id' => $userId,
                    'role_id'  => $role,
                ]);
            }
        }
    }

    /**
     * Remove role dari user
     *
     * @param int $userId
     * @param int|array $roleId
     * @return void
     */
    public function removeRole($userId, $roleId)
    {
        $query = $this->model->where('users_id', $userId);

        if (is_array($roleId)) {
            $query->whereIn('role_id', $roleId);
        } else {
            $query->where('role_id', $roleId);
        }

        $query->delete();
    }
}
