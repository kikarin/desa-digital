<?php

namespace App\Repositories;

use App\Models\ResidentStatus;
use App\Traits\RepositoryTrait;

class ResidentStatusRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(ResidentStatus $model)
    {
        $this->model = $model;
        $this->with = ['created_by_user', 'updated_by_user'];
    }

    public function customIndex($data)
    {
        $query = $this->model->select('id', 'code', 'name');

        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('code', 'like', '%' . $searchTerm . '%')
                    ->orWhere('name', 'like', '%' . $searchTerm . '%');
            });
        }

        if (request('sort')) {
            $order = request('order', 'asc');
            $sortMapping = [
                'code' => 'code',
                'name' => 'name',
            ];

            $sortColumn = $sortMapping[request('sort')] ?? 'id';
            $query->orderBy($sortColumn, $order);
        } else {
            $query->orderBy('id', 'desc');
        }

        $perPage = (int) request('per_page', 10);
        
        if ($perPage === -1) {
            $allStatuses = $query->get();
            $transformedStatuses = $allStatuses->map(function ($status) {
                return [
                    'id'   => $status->id,
                    'code' => $status->code,
                    'name' => $status->name,
                ];
            });

            $data += [
                'resident_statuses' => $transformedStatuses,
                'meta' => [
                    'total'        => $transformedStatuses->count(),
                    'current_page' => 1,
                    'per_page'     => -1,
                    'search'       => request('search', ''),
                    'sort'         => request('sort', ''),
                    'order'        => request('order', 'asc'),
                ],
            ];
            return $data;
        }

        $page           = (int) request('page', 0);
        $pageForLaravel = $page < 1 ? 1 : $page + 1;
        $statuses       = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);

        $transformedStatuses = $statuses->getCollection()->map(function ($status) {
            return [
                'id'   => $status->id,
                'code' => $status->code,
                'name' => $status->name,
            ];
        });

        $data += [
            'resident_statuses' => $transformedStatuses,
            'meta' => [
                'total'        => $statuses->total(),
                'current_page' => $statuses->currentPage(),
                'per_page'     => $statuses->perPage(),
                'search'       => request('search', ''),
                'sort'         => request('sort', ''),
                'order'        => request('order', 'asc'),
            ],
        ];

        return $data;
    }

    public function customCreateEdit($data, $item = null)
    {
        return $data;
    }

    public function customShow($data, $item = null)
    {
        return $data;
    }
}

