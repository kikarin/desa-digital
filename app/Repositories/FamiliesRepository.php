<?php

namespace App\Repositories;

use App\Models\Families;
use App\Traits\RepositoryTrait;
use App\Repositories\HousesRepository;

class FamiliesRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(Families $model)
    {
        $this->model = $model;
        $this->with = ['house.rt.rw', 'created_by_user', 'updated_by_user'];
    }

    public function customIndex($data)
    {
        $query = $this->model->with('house.rt.rw')
            ->select('families.id', 'families.house_id', 'families.no_kk', 'families.kepala_keluarga_id', 'families.status',
                     'houses.nomor_rumah', 'rts.nomor_rt', 'rws.nomor_rw', 'rws.desa', 'rws.kecamatan', 'rws.kabupaten')
            ->leftJoin('houses', 'families.house_id', '=', 'houses.id')
            ->leftJoin('rts', 'houses.rt_id', '=', 'rts.id')
            ->leftJoin('rws', 'rts.rw_id', '=', 'rws.id');

        if (request('filter_rw_id')) {
            $query->where('rws.id', request('filter_rw_id'));
        }

        if (request('filter_rt_id')) {
            $query->where('rts.id', request('filter_rt_id'));
        }

        if (request('filter_nomor_rumah')) {
            $query->where('houses.nomor_rumah', 'like', '%' . request('filter_nomor_rumah') . '%');
        }

        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('families.no_kk', 'like', '%' . $searchTerm . '%')
                    ->orWhere('families.status', 'like', '%' . $searchTerm . '%')
                    ->orWhere('houses.nomor_rumah', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rts.nomor_rt', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rws.nomor_rw', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rws.desa', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rws.kecamatan', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rws.kabupaten', 'like', '%' . $searchTerm . '%');
            });
        }

        if (request('sort')) {
            $order = request('order', 'asc');
            $sortMapping = [
                'no_kk'        => 'families.no_kk',
                'status'       => 'families.status',
                'nomor_rumah'  => 'houses.nomor_rumah',
                'rt'           => 'rts.nomor_rt',
                'rw'           => 'rws.nomor_rw',
                'desa'         => 'rws.desa',
                'kecamatan'    => 'rws.kecamatan',
                'kabupaten'    => 'rws.kabupaten',
            ];
            $sortColumn = $sortMapping[request('sort')] ?? 'families.id';
            $query->orderBy($sortColumn, $order);
        } else {
            $query->orderBy('families.id', 'desc');
        }

        $perPage        = (int) request('per_page', 10);
        $page           = (int) request('page', 0);
        $pageForLaravel = $page < 1 ? 1 : $page + 1;

        if ($perPage === -1) {
            $families = $query->get();
            $transformedFamilies = $families->map(function ($family) {
                return [
                    'id'         => $family->id,
                    'no_kk'      => $family->no_kk,
                    'status'     => $family->status,
                    'nomor_rumah' => $family->nomor_rumah,
                    'rt'         => $family->nomor_rt,
                    'rw'         => $family->nomor_rw,
                    'desa'       => $family->desa,
                    'kecamatan'  => $family->kecamatan,
                    'kabupaten'  => $family->kabupaten,
                ];
            });
            $data += [
                'families' => $transformedFamilies,
                'meta'     => [
                    'total'        => $families->count(),
                    'current_page' => 1,
                    'per_page'     => $families->count(),
                    'search'       => request('search', ''),
                    'sort'         => request('sort', ''),
                    'order'        => request('order', 'asc'),
                ],
            ];
            return $data;
        }

        $families = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);
        $transformedFamilies = $families->getCollection()->map(function ($family) {
            return [
                'id'          => $family->id,
                'no_kk'       => $family->no_kk,
                'status'      => $family->status,
                'nomor_rumah' => $family->nomor_rumah,
                'rt'          => $family->nomor_rt,
                'rw'          => $family->nomor_rw,
                'desa'        => $family->desa,
                'kecamatan'   => $family->kecamatan,
                'kabupaten'   => $family->kabupaten,
            ];
        });
        $data += [
            'families' => $transformedFamilies,
            'meta'     => [
                'total'        => $families->total(),
                'current_page' => $families->currentPage(),
                'per_page'     => $families->perPage(),
                'search'       => request('search', ''),
                'sort'         => request('sort', ''),
                'order'        => request('order', 'asc'),
            ],
        ];
        return $data;
    }

    public function customCreateEdit($data, $item = null)
    {
        $housesRepository = app(HousesRepository::class);
        $houses = $housesRepository->getAll([], false, false);
        $houses->load('rt.rw');

        $data += [
            'listHouse' => $houses->map(function ($house) {
                return [
                    'value' => $house->id,
                    'label' => $house->nomor_rumah . ' - RT ' . ($house->rt->nomor_rt ?? '') . ' - RW ' . ($house->rt->rw->nomor_rw ?? '') . ' - ' . ($house->rt->rw->desa ?? ''),
                ];
            })->toArray(),
        ];

        return $data;
    }

    public function customShow($data, $item)
    {
        $item->load(['residents.status', 'kepala_keluarga']);

        $kepalaKeluarga = $item->kepala_keluarga;
        $kepalaKeluargaInfo = '-';
        if ($kepalaKeluarga) {
            $kepalaKeluargaInfo = $kepalaKeluarga->nik . ' - ' . $kepalaKeluarga->nama;
        }

        $fields = [
            ['label' => 'No. KK', 'value' => $item->no_kk ?? '-'],
            ['label' => 'Rumah', 'value' =>'NO '. ($item->house-> nomor_rumah ?? '-') . ' - RT ' . ($item->house->rt->nomor_rt ?? '') . ' - RW ' . ($item->house->rt->rw->nomor_rw ?? '') . ' - ' . ($item->house->rt->rw->desa ?? '')],
            ['label' => 'Status', 'value' => $item->status ?? '-'],
            ['label' => 'Kepala Keluarga', 'value' => $kepalaKeluargaInfo],
        ];

        $actionFields = [
            ['label' => 'Created At', 'value' => $item->created_at ? date('Y-m-d H:i:s', strtotime($item->created_at)) : '-'],
            ['label' => 'Created By', 'value' => optional($item->created_by_user)->name ?? '-'],
            ['label' => 'Updated At', 'value' => $item->updated_at ? date('Y-m-d H:i:s', strtotime($item->updated_at)) : '-'],
            ['label' => 'Updated By', 'value' => optional($item->updated_by_user)->name ?? '-'],
        ];

        $residents = [];
        foreach ($item->residents as $resident) {
            $residents[] = [
                'id' => $resident->id,
                'nik' => $resident->nik,
                'nama' => $resident->nama,
                'status' => $resident->status->name ?? '-',
                'is_kepala_keluarga' => $resident->id === $item->kepala_keluarga_id,
                'tempat_lahir' => $resident->tempat_lahir,
                'tanggal_lahir' => $resident->tanggal_lahir,
                'jenis_kelamin' => $resident->jenis_kelamin,
            ];
        }

        $data += [
            'fields'       => $fields,
            'actionFields' => $actionFields,
            'residents'    => $residents,
        ];

        return $data;
    }
}

