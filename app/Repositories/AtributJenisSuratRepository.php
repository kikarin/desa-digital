<?php

namespace App\Repositories;

use App\Models\AtributJenisSurat;
use App\Traits\RepositoryTrait;

class AtributJenisSuratRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(AtributJenisSurat $model)
    {
        $this->model = $model;
        $this->with = ['jenisSurat', 'created_by_user', 'updated_by_user'];
    }

    public function customIndex($data)
    {
        $query = $this->model->with('jenisSurat')
            ->select('id', 'jenis_surat_id', 'nama_atribut', 'tipe_data', 'is_required', 'urutan');

        if (request('filter_jenis_surat_id')) {
            $query->where('jenis_surat_id', request('filter_jenis_surat_id'));
        }

        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nama_atribut', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('jenisSurat', function ($q) use ($searchTerm) {
                        $q->where('nama', 'like', '%' . $searchTerm . '%');
                    });
            });
        }

        if (request('sort')) {
            $order = request('order', 'asc');
            $sortMapping = [
                'nama_atribut' => 'nama_atribut',
                'tipe_data' => 'tipe_data',
                'urutan' => 'urutan',
            ];

            $sortColumn = $sortMapping[request('sort')] ?? 'urutan';
            $query->orderBy($sortColumn, $order);
        } else {
            $query->orderBy('urutan', 'asc')->orderBy('id', 'asc');
        }

        $perPage = (int) request('per_page', 10);
        
        if ($perPage === -1) {
            $allData = $query->get();
            $transformedData = $allData->map(function ($item) {
                return [
                    'id' => $item->id,
                    'jenis_surat_id' => $item->jenis_surat_id,
                    'jenis_surat_nama' => $item->jenisSurat->nama ?? '-',
                    'nama_atribut' => $item->nama_atribut,
                    'tipe_data' => $item->tipe_data,
                    'is_required' => $item->is_required,
                    'urutan' => $item->urutan,
                ];
            });

            $data += [
                'atribut_jenis_surat' => $transformedData,
                'meta' => [
                    'total' => $transformedData->count(),
                    'current_page' => 1,
                    'per_page' => -1,
                    'search' => request('search', ''),
                    'sort' => request('sort', ''),
                    'order' => request('order', 'asc'),
                ],
            ];
            return $data;
        }

        $page = (int) request('page', 0);
        $pageForLaravel = $page < 1 ? 1 : $page + 1;
        $result = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);

        $transformedData = $result->getCollection()->map(function ($item) {
            return [
                'id' => $item->id,
                'jenis_surat_id' => $item->jenis_surat_id,
                'jenis_surat_nama' => $item->jenisSurat->nama ?? '-',
                'nama_atribut' => $item->nama_atribut,
                'tipe_data' => $item->tipe_data,
                'is_required' => $item->is_required,
                'urutan' => $item->urutan,
            ];
        });

        $data += [
            'atribut_jenis_surat' => $transformedData,
            'meta' => [
                'total' => $result->total(),
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'search' => request('search', ''),
                'sort' => request('sort', ''),
                'order' => request('order', 'asc'),
            ],
        ];

        return $data;
    }

    public function customCreateEdit($data, $item = null)
    {
        // Get jenis surat options for dropdown
        $data['jenis_surat_options'] = \App\Models\JenisSurat::orderBy('nama', 'asc')
            ->pluck('nama', 'id')
            ->toArray();
        return $data;
    }

    public function customShow($data, $item = null)
    {
        if ($item) {
            $data['jenis_surat'] = $item->jenisSurat;
        }
        return $data;
    }
}

