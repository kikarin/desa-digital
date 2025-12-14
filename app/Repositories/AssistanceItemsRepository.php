<?php

namespace App\Repositories;

use App\Models\AssistanceItem;
use App\Traits\RepositoryTrait;

class AssistanceItemsRepository
{
    use RepositoryTrait;

    protected $model;

    /**
     * Constructor - set model dan relasi yang akan di-load
     */
    public function __construct(AssistanceItem $model)
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
        $query = $this->model->select('id', 'nama_item', 'tipe', 'satuan');

        // Filter berdasarkan tipe
        if (request('filter_tipe')) {
            $query->where('tipe', request('filter_tipe'));
        }

        // Cari data berdasarkan keyword
        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nama_item', 'like', '%' . $searchTerm . '%')
                    ->orWhere('satuan', 'like', '%' . $searchTerm . '%');
            });
        }

        // Sorting data
        if (request('sort')) {
            $order = request('order', 'asc');
            $sortMapping = [
                'nama_item' => 'nama_item',
                'tipe'     => 'tipe',
                'satuan'   => 'satuan',
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
            $allItems = $query->get();
            $transformedItems = $allItems->map(function ($item) {
                return [
                    'id'        => $item->id,
                    'nama_item' => $item->nama_item,
                    'tipe'      => $item->tipe,
                    'satuan'    => $item->satuan,
                ];
            });

            $data += [
                'assistance_items' => $transformedItems,
                'meta' => [
                    'total'        => $transformedItems->count(),
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
        $items          = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);

        // Transform data untuk frontend
        $transformedItems = $items->getCollection()->map(function ($item) {
            return [
                'id'        => $item->id,
                'nama_item' => $item->nama_item,
                'tipe'      => $item->tipe,
                'satuan'    => $item->satuan,
            ];
        });

        $data += [
            'assistance_items' => $transformedItems,
            'meta' => [
                'total'        => $items->total(),
                'current_page' => $items->currentPage(),
                'per_page'     => $items->perPage(),
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
        return $data;
    }
}

