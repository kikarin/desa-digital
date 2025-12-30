<?php

namespace App\Repositories;

use App\Models\JenisSurat;
use App\Models\AtributJenisSurat;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\DB;

class JenisSuratRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(JenisSurat $model)
    {
        $this->model = $model;
        $this->with = ['created_by_user', 'updated_by_user', 'atribut'];
    }

    public function customIndex($data)
    {
        $query = $this->model->select('id', 'nama', 'kode');

        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nama', 'like', '%' . $searchTerm . '%')
                    ->orWhere('kode', 'like', '%' . $searchTerm . '%');
            });
        }

        if (request('sort')) {
            $order = request('order', 'asc');
            $sortMapping = [
                'nama' => 'nama',
                'kode' => 'kode',
            ];

            $sortColumn = $sortMapping[request('sort')] ?? 'id';
            $query->orderBy($sortColumn, $order);
        } else {
            $query->orderBy('id', 'desc');
        }

        $perPage = (int) request('per_page', 10);
        
        if ($perPage === -1) {
            $allData = $query->get();
            $transformedData = $allData->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'kode' => $item->kode,
                ];
            });

            $data += [
                'jenis_surat' => $transformedData,
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
                'nama' => $item->nama,
                'kode' => $item->kode,
            ];
        });

        $data += [
            'jenis_surat' => $transformedData,
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
        if ($item) {
            // Load existing atribut for editing
            $data['atribut'] = $item->atribut->map(function ($atribut) {
                return [
                    'id' => $atribut->id,
                    'nama_atribut' => $atribut->nama_atribut,
                    'tipe_data' => $atribut->tipe_data,
                    'opsi_pilihan' => $atribut->opsi_pilihan,
                    'opsi_pilihan_array' => $atribut->opsi_pilihan_array,
                    'is_required' => $atribut->is_required,
                    'nama_lampiran' => $atribut->nama_lampiran,
                    'minimal_file' => $atribut->minimal_file,
                    'is_required_lampiran' => $atribut->is_required_lampiran,
                    'urutan' => $atribut->urutan,
                ];
            });
        }
        return $data;
    }

    public function customShow($data, $item = null)
    {
        if ($item) {
            $data['atribut'] = $item->atribut->map(function ($atribut) {
                return [
                    'id' => $atribut->id,
                    'nama_atribut' => $atribut->nama_atribut,
                    'tipe_data' => $atribut->tipe_data,
                    'opsi_pilihan' => $atribut->opsi_pilihan,
                    'opsi_pilihan_array' => $atribut->opsi_pilihan_array,
                    'is_required' => $atribut->is_required,
                    'nama_lampiran' => $atribut->nama_lampiran,
                    'minimal_file' => $atribut->minimal_file,
                    'is_required_lampiran' => $atribut->is_required_lampiran,
                    'urutan' => $atribut->urutan,
                ];
            });
        }
        return $data;
    }
}

