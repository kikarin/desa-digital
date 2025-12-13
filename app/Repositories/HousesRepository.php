<?php

namespace App\Repositories;

use App\Models\Houses;
use App\Traits\RepositoryTrait;
use App\Repositories\RtsRepository;

class HousesRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(Houses $model)
    {
        $this->model = $model;
        $this->with = ['rt.rw', 'created_by_user', 'updated_by_user'];
    }

    public function customIndex($data)
    {
        $query = $this->model->with(['rt.rw', 'families.residents' => function ($query) {
            $query->with('status');
        }])
            ->select('houses.id', 'houses.rt_id', 'houses.nomor_rumah', 'houses.jenis_rumah', 'houses.keterangan', 
                     'rts.nomor_rt', 'rws.nomor_rw', 'rws.desa', 'rws.kecamatan', 'rws.kabupaten')
            ->leftJoin('rts', 'houses.rt_id', '=', 'rts.id')
            ->leftJoin('rws', 'rts.rw_id', '=', 'rws.id');

        if (request('filter_rw_id')) {
            $query->where('rws.id', request('filter_rw_id'));
        }

        if (request('filter_rt_id')) {
            $query->where('houses.rt_id', request('filter_rt_id'));
        }

        if (request('filter_jenis_rumah')) {
            $query->where('houses.jenis_rumah', request('filter_jenis_rumah'));
        }

        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('houses.nomor_rumah', 'like', '%' . $searchTerm . '%')
                    ->orWhere('houses.jenis_rumah', 'like', '%' . $searchTerm . '%')
                    ->orWhere('houses.keterangan', 'like', '%' . $searchTerm . '%')
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
                'nomor_rumah' => 'houses.nomor_rumah',
                'jenis_rumah' => 'houses.jenis_rumah',
                'rt'          => 'rts.nomor_rt',
                'rw'          => 'rws.nomor_rw',
                'desa'        => 'rws.desa',
                'kecamatan'   => 'rws.kecamatan',
                'kabupaten'   => 'rws.kabupaten',
            ];

            $sortColumn = $sortMapping[request('sort')] ?? 'houses.id';
            $query->orderBy($sortColumn, $order);
        } else {
            $query->orderBy('houses.id', 'desc');
        }

        $perPage = (int) request('per_page', 10);
        
        if ($perPage === -1) {
            $allHouses = $query->get();
            $transformedHouses = $allHouses->map(function ($house) {
                $totalResidents = 0;
                foreach ($house->families as $family) {
                    foreach ($family->residents as $resident) {
                        $statusName = $resident->status->name ?? '';
                        if ($statusName !== 'Meninggal' && $statusName !== 'Pindah') {
                            $totalResidents++;
                        }
                    }
                }
                
                return [
                    'id'          => $house->id,
                    'nomor_rumah' => $house->nomor_rumah,
                    'jenis_rumah' => $house->jenis_rumah,
                    'rt_id'       => $house->rt_id,
                    'rt'          => $house->nomor_rt,
                    'rw'          => $house->nomor_rw . ' - ' . $house->desa,
                    'desa'        => $house->desa,
                    'kecamatan'   => $house->kecamatan,
                    'kabupaten'  => $house->kabupaten,
                    'keterangan' => $house->keterangan,
                    'total_residents' => $totalResidents,
                ];
            });

            $data += [
                'houses' => $transformedHouses,
                'meta' => [
                    'total'        => $transformedHouses->count(),
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
        $houses         = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);

        $transformedHouses = $houses->getCollection()->map(function ($house) {
            $totalResidents = 0;
            foreach ($house->families as $family) {
                foreach ($family->residents as $resident) {
                    $statusName = $resident->status->name ?? '';
                    if ($statusName !== 'Meninggal' && $statusName !== 'Pindah') {
                        $totalResidents++;
                    }
                }
            }
            
            return [
                'id'          => $house->id,
                'nomor_rumah' => $house->nomor_rumah,
                'jenis_rumah' => $house->jenis_rumah,
                'rt_id'       => $house->rt_id,
                'rt'          => $house->nomor_rt,
                'rw'          => $house->nomor_rw . ' - ' . $house->desa,
                'desa'        => $house->desa,
                'kecamatan'   => $house->kecamatan,
                'kabupaten'  => $house->kabupaten,
                'keterangan' => $house->keterangan,
                'total_residents' => $totalResidents,
            ];
        });

        $data += [
            'houses' => $transformedHouses,
            'meta' => [
                'total'        => $houses->total(),
                'current_page' => $houses->currentPage(),
                'per_page'     => $houses->perPage(),
                'search'       => request('search', ''),
                'sort'         => request('sort', ''),
                'order'        => request('order', 'asc'),
            ],
        ];

        return $data;
    }

    public function customCreateEdit($data, $item = null)
    {
        $rtsRepository = app(RtsRepository::class);
        $rts = $rtsRepository->getAll([], false, false);
        
        $rts->load('rw');
        
        $residentsRepository = app(\App\Repositories\ResidentsRepository::class);
        $residents = $residentsRepository->getAll([], false, false);
        
        $data += [
            'listRt' => $rts->map(function ($rt) {
                $rw = $rt->rw;
                $label = $rt->nomor_rt;
                if ($rw) {
                    $label .= ' - RW ' . $rw->nomor_rw . ' - ' . $rw->desa;
                }
                return [
                    'value' => $rt->id,
                    'label' => $label,
                ];
            })->toArray(),
            'listResidents' => $residents->map(function ($resident) {
                return [
                    'value' => $resident->id,
                    'label' => $resident->nik . ' - ' . $resident->nama,
                ];
            })->toArray(),
        ];

        return $data;
    }

    public function customShow($data, $item = null)
    {
        if ($item) {
            $item->load([
                'families.residents' => function ($query) {
                    $query->with('status');
                }, 
                'families.kepala_keluarga',
                'pemilik'
            ]);
            
            $residents = [];
            foreach ($item->families as $family) {
                foreach ($family->residents as $resident) {
                    $residents[] = [
                        'id' => $resident->id,
                        'nik' => $resident->nik,
                        'nama' => $resident->nama,
                        'status' => $resident->status->name ?? '-',
                        'family_id' => $family->id,
                        'no_kk' => $family->no_kk,
                        'is_kepala_keluarga' => $resident->id === $family->kepala_keluarga_id,
                        'jenis_kelamin' => $resident->jenis_kelamin,
                        'tanggal_lahir' => $resident->tanggal_lahir,
                    ];
                }
            }
            
            $data['residents'] = $residents;
        }
        
        return $data;
    }
}

