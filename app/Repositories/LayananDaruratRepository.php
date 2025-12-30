<?php

namespace App\Repositories;

use App\Models\LayananDarurat;
use App\Traits\RepositoryTrait;

class LayananDaruratRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(LayananDarurat $model)
    {
        $this->model = $model;
        $this->with = ['created_by_user', 'updated_by_user'];
    }

    public function customIndex($data)
    {
        $query = $this->model->select('id', 'kategori', 'latitude', 'longitude', 'title', 'alamat', 'nomor_whatsapp');

        // Search
        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                    ->orWhere('alamat', 'like', '%' . $searchTerm . '%')
                    ->orWhere('nomor_whatsapp', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter by kategori
        if (request('kategori')) {
            $query->where('kategori', request('kategori'));
        }

        // Sorting
        if (request('sort')) {
            $order = request('order', 'asc');
            $sortMapping = [
                'title' => 'title',
                'alamat' => 'alamat',
                'kategori' => 'kategori',
            ];

            $sortColumn = $sortMapping[request('sort')] ?? 'id';
            $query->orderBy($sortColumn, $order);
        } else {
            $query->orderBy('id', 'desc');
        }

        // Pagination
        $perPage = (int) request('per_page', 10);
        
        if ($perPage === -1) {
            $allData = $query->get();
            $transformedData = $allData->map(function ($item) {
                return $this->transformItem($item);
            });

            $data += [
                'layanan_darurat' => $transformedData,
                'meta' => [
                    'total'        => $transformedData->count(),
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
        $items          = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);

        $transformedData = $items->getCollection()->map(function ($item) {
            return $this->transformItem($item);
        });

        $data += [
            'layanan_darurat' => $transformedData,
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

    private function transformItem($item)
    {
        return [
            'id'             => $item->id,
            'kategori'       => $item->kategori,
            'kategori_label' => $item->kategori_label,
            'latitude'       => $item->latitude,
            'longitude'      => $item->longitude,
            'title'          => $item->title,
            'alamat'         => $item->alamat,
            'nomor_whatsapp' => $item->nomor_whatsapp,
        ];
    }

    public function customCreateEdit($data, $item = null)
    {
        if ($item) {
            // Transform item untuk form edit
            $data['item'] = [
                'id'             => $item->id,
                'kategori'       => $item->kategori,
                'latitude'       => $item->latitude,
                'longitude'      => $item->longitude,
                'title'          => $item->title,
                'alamat'         => $item->alamat,
                'nomor_whatsapp' => $item->nomor_whatsapp,
            ];
        }
        return $data;
    }

    public function customShow($data, $item = null)
    {
        if ($item) {
            $data['item'] = [
                'id'             => $item->id,
                'kategori'       => $item->kategori,
                'kategori_label' => $item->kategori_label,
                'latitude'       => $item->latitude,
                'longitude'      => $item->longitude,
                'title'          => $item->title,
                'alamat'         => $item->alamat,
                'nomor_whatsapp' => $item->nomor_whatsapp,
                'created_at'     => $item->created_at?->format('Y-m-d H:i:s'),
                'updated_at'     => $item->updated_at?->format('Y-m-d H:i:s'),
                'created_by_user' => $item->created_by_user ? [
                    'id' => $item->created_by_user->id,
                    'name' => $item->created_by_user->name,
                ] : null,
                'updated_by_user' => $item->updated_by_user ? [
                    'id' => $item->updated_by_user->id,
                    'name' => $item->updated_by_user->name,
                ] : null,
            ];
        }
        return $data;
    }

    public function customDataCreateUpdate($data, $record = null)
    {
        // Clean nomor_whatsapp (remove non-numeric characters except +)
        if (isset($data['nomor_whatsapp'])) {
            $data['nomor_whatsapp'] = preg_replace('/[^0-9+]/', '', $data['nomor_whatsapp']);
        }

        return $data;
    }
}

