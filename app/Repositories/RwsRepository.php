<?php

namespace App\Repositories;

use App\Models\Rws;
use App\Traits\RepositoryTrait;

class RwsRepository
{
    use RepositoryTrait;

    protected $model;

    /**
     * Constructor - set model dan relasi yang akan di-load
     */
    public function __construct(Rws $model)
    {
        $this->model = $model;
        // Load relasi user untuk tracking
        $this->with = ['created_by_user', 'updated_by_user'];
    }

    /**
     * Custom data untuk halaman index
     * Handle search, sort, dan pagination
     */
    public function customIndex($data)
    {
        $query = $this->model->select('id', 'nomor_rw', 'desa', 'kecamatan', 'kabupaten', 'boundary');

        // Cari data berdasarkan keyword
        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nomor_rw', 'like', '%' . $searchTerm . '%')
                    ->orWhere('desa', 'like', '%' . $searchTerm . '%')
                    ->orWhere('kecamatan', 'like', '%' . $searchTerm . '%')
                    ->orWhere('kabupaten', 'like', '%' . $searchTerm . '%');
            });
        }

        // Sorting data
        if (request('sort')) {
            $order = request('order', 'asc');
            $sortMapping = [
                'nomor_rw'  => 'nomor_rw',
                'desa'      => 'desa',
                'kecamatan' => 'kecamatan',
                'kabupaten' => 'kabupaten',
            ];

            $sortColumn = $sortMapping[request('sort')] ?? 'id';
            $query->orderBy($sortColumn, $order);
        } else {
            $query->orderBy('id', 'desc'); // Default sort by id desc
        }

        // Pagination
        $perPage = (int) request('per_page', 10);
        
        // Kalau per_page = -1, ambil semua data
        if ($perPage === -1) {
            $allRws = $query->get();
            $transformedRws = $allRws->map(function ($rws) {
                // Cek apakah RW ini sudah punya akun
                $hasAccount = \App\Models\UsersRole::where('rw_id', $rws->id)
                    ->where('role_id', 35) // RW role ID
                    ->exists();
                
                return [
                    'id'         => $rws->id,
                    'nomor_rw'   => $rws->nomor_rw,
                    'desa'       => $rws->desa,
                    'kecamatan'  => $rws->kecamatan,
                    'kabupaten'  => $rws->kabupaten,
                    'boundary'   => $rws->boundary,
                    'has_account' => $hasAccount,
                ];
            });

            $data += [
                'rws' => $transformedRws,
                'meta' => [
                    'total'        => $transformedRws->count(),
                    'current_page' => 1,
                    'per_page'     => -1,
                    'search'       => request('search', ''),
                    'sort'         => request('sort', ''),
                    'order'        => request('order', 'asc'),
                ],
            ];
            return $data;
        }

        // Pagination normal
        $page           = (int) request('page', 0);
        $pageForLaravel = $page < 1 ? 1 : $page + 1;
        $rws            = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);

        // Transform data untuk frontend
        $transformedRws = $rws->getCollection()->map(function ($rws) {
            // Cek apakah RW ini sudah punya akun
            $hasAccount = \App\Models\UsersRole::where('rw_id', $rws->id)
                ->where('role_id', 35) // RW role ID
                ->exists();
            
            return [
                'id'         => $rws->id,
                'nomor_rw'   => $rws->nomor_rw,
                'desa'       => $rws->desa,
                'kecamatan'  => $rws->kecamatan,
                'kabupaten'  => $rws->kabupaten,
                'has_account' => $hasAccount,
            ];
        });

        $data += [
            'rws' => $transformedRws,
            'meta' => [
                'total'        => $rws->total(),
                'current_page' => $rws->currentPage(),
                'per_page'     => $rws->perPage(),
                'search'       => request('search', ''),
                'sort'         => request('sort', ''),
                'order'        => request('order', 'asc'),
            ],
        ];

        return $data;
    }

    /**
     * Custom data untuk halaman create/edit
     */
    public function customCreateEdit($data, $item = null)
    {
        return $data;
    }

    /**
     * Custom data untuk halaman show/detail
     */
    public function customShow($data, $item = null)
    {
        if ($item) {
            // Cek apakah RW ini sudah punya akun
            $userRole = \App\Models\UsersRole::where('rw_id', $item->id)
                ->where('role_id', 35) // RW role ID
                ->with('users')
                ->first();
            
            $data['has_account'] = $userRole ? true : false;
            $data['user_account'] = $userRole ? [
                'id' => $userRole->users->id ?? null,
                'name' => $userRole->users->name ?? null,
                'email' => $userRole->users->email ?? null,
            ] : null;
        }
        
        return $data;
    }
}

