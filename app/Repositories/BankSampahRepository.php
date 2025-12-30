<?php

namespace App\Repositories;

use App\Models\BankSampah;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Storage;

class BankSampahRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(BankSampah $model)
    {
        $this->model = $model;
        $this->with = ['created_by_user', 'updated_by_user'];
    }

    public function customIndex($data)
    {
        $query = $this->model->select('id', 'latitude', 'longitude', 'nama_lokasi', 'alamat', 'title', 'foto', 'deskripsi');

        // Search
        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nama_lokasi', 'like', '%' . $searchTerm . '%')
                    ->orWhere('alamat', 'like', '%' . $searchTerm . '%')
                    ->orWhere('title', 'like', '%' . $searchTerm . '%')
                    ->orWhere('deskripsi', 'like', '%' . $searchTerm . '%');
            });
        }

        // Sorting
        if (request('sort')) {
            $order = request('order', 'asc');
            $sortMapping = [
                'nama_lokasi' => 'nama_lokasi',
                'title' => 'title',
                'alamat' => 'alamat',
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
                'bank_sampah' => $transformedData,
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
            'bank_sampah' => $transformedData,
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
            'id'         => $item->id,
            'latitude'   => $item->latitude,
            'longitude'  => $item->longitude,
            'nama_lokasi' => $item->nama_lokasi,
            'alamat'     => $item->alamat,
            'title'      => $item->title,
            'foto'       => $item->foto ? asset('storage/' . $item->foto) : null,
            'deskripsi'  => $item->deskripsi,
        ];
    }

    public function customCreateEdit($data, $item = null)
    {
        if ($item) {
            // Transform item untuk form edit
            $data['item'] = [
                'id'         => $item->id,
                'latitude'   => $item->latitude,
                'longitude'  => $item->longitude,
                'nama_lokasi' => $item->nama_lokasi,
                'alamat'     => $item->alamat,
                'title'      => $item->title,
                'foto'       => $item->foto ? asset('storage/' . $item->foto) : null,
                'deskripsi'  => $item->deskripsi,
            ];
        }
        return $data;
    }

    public function customShow($data, $item = null)
    {
        if ($item) {
            $data['item'] = [
                'id'         => $item->id,
                'latitude'   => $item->latitude,
                'longitude'  => $item->longitude,
                'nama_lokasi' => $item->nama_lokasi,
                'alamat'     => $item->alamat,
                'title'      => $item->title,
                'foto'       => $item->foto ? asset('storage/' . $item->foto) : null,
                'deskripsi'  => $item->deskripsi,
                'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $item->updated_at?->format('Y-m-d H:i:s'),
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
        // Handle file upload
        if (isset($data['foto']) && is_object($data['foto']) && method_exists($data['foto'], 'isValid')) {
            // File upload object
            if ($data['foto']->isValid()) {
                // Delete old file if exists
                if ($record && $record->foto) {
                    Storage::disk('public')->delete($record->foto);
                }
                // Store new file
                $path = $data['foto']->store('bank-sampah', 'public');
                $data['foto'] = $path;
            } else {
                // Invalid file, keep existing if edit mode
                if ($record && $record->foto) {
                    $data['foto'] = $record->foto;
                } else {
                    unset($data['foto']);
                }
            }
        } elseif (isset($data['foto']) && $data['foto'] === '' && $record) {
            // Empty string means keep existing foto
            $data['foto'] = $record->foto;
        } elseif (isset($data['foto']) && $data['foto'] === null && $record) {
            // Explicitly null means delete foto
            if ($record->foto) {
                Storage::disk('public')->delete($record->foto);
            }
            $data['foto'] = null;
        } elseif (!isset($data['foto']) || (isset($data['foto']) && $data['foto'] === '')) {
            // No foto provided or empty string - keep existing if edit mode
            if ($record && $record->foto) {
                $data['foto'] = $record->foto;
            } else {
                unset($data['foto']);
            }
        }

        return $data;
    }
}

