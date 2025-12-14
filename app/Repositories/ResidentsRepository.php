<?php

namespace App\Repositories;

use App\Models\Residents;
use App\Traits\RepositoryTrait;
use App\Repositories\FamiliesRepository;
use App\Repositories\ResidentStatusRepository;
use App\Models\ResidentMoves;
use App\Models\ResidentDeaths;
use App\Models\ResidentStatus;

class ResidentsRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(Residents $model)
    {
        $this->model = $model;
        $this->with = ['family.house.rt.rw', 'status', 'created_by_user', 'updated_by_user'];
    }

    public function customIndex($data)
    {
        $query = $this->model->with('family.house.rt.rw', 'status')
            ->select('residents.id', 'residents.family_id', 'residents.nik', 'residents.nama', 
                     'residents.tempat_lahir', 'residents.tanggal_lahir', 'residents.jenis_kelamin',
                     'residents.status_id', 'residents.status_note',
                     'families.no_kk', 'houses.nomor_rumah', 'rts.nomor_rt', 'rws.nomor_rw', 
                     'rws.desa', 'rws.kecamatan', 'rws.kabupaten', 'resident_statuses.name as status_name')
            ->leftJoin('families', 'residents.family_id', '=', 'families.id')
            ->leftJoin('houses', 'families.house_id', '=', 'houses.id')
            ->leftJoin('rts', 'houses.rt_id', '=', 'rts.id')
            ->leftJoin('rws', 'rts.rw_id', '=', 'rws.id')
            ->leftJoin('resident_statuses', 'residents.status_id', '=', 'resident_statuses.id');

        if (request('filter_rw_id')) {
            $query->where('rws.id', request('filter_rw_id'));
        }

        if (request('filter_rt_id')) {
            $query->where('rts.id', request('filter_rt_id'));
        }

        if (request('filter_status_id')) {
            $query->where('residents.status_id', request('filter_status_id'));
        }

        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('residents.nik', 'like', '%' . $searchTerm . '%')
                    ->orWhere('residents.nama', 'like', '%' . $searchTerm . '%')
                    ->orWhere('residents.tempat_lahir', 'like', '%' . $searchTerm . '%')
                    ->orWhere('families.no_kk', 'like', '%' . $searchTerm . '%')
                    ->orWhere('houses.nomor_rumah', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rts.nomor_rt', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rws.nomor_rw', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rws.desa', 'like', '%' . $searchTerm . '%');
            });
        }

        if (request('sort')) {
            $order = request('order', 'asc');
            $sortMapping = [
                'nik'          => 'residents.nik',
                'nama'         => 'residents.nama',
                'tanggal_lahir' => 'residents.tanggal_lahir',
                'jenis_kelamin' => 'residents.jenis_kelamin',
                'status'       => 'resident_statuses.name',
                'no_kk'        => 'families.no_kk',
                'nomor_rumah'  => 'houses.nomor_rumah',
                'rt'           => 'rts.nomor_rt',
                'rw'           => 'rws.nomor_rw',
            ];
            $sortColumn = $sortMapping[request('sort')] ?? 'residents.id';
            $query->orderBy($sortColumn, $order);
        } else {
            $query->orderBy('residents.id', 'desc');
        }

        $perPage        = (int) request('per_page', 10);
        $page           = (int) request('page', 0);
        $pageForLaravel = $page < 1 ? 1 : $page + 1;

        if ($perPage === -1) {
            $residents = $query->get();
            $transformedResidents = $residents->map(function ($resident) {
                return [
                    'id'            => $resident->id,
                    'nik'           => $resident->nik,
                    'nama'          => $resident->nama,
                    'tempat_lahir'  => $resident->tempat_lahir,
                    'tanggal_lahir' => $resident->tanggal_lahir,
                    'jenis_kelamin' => $resident->jenis_kelamin,
                    'status'        => $resident->status_name,
                    'no_kk'         => $resident->no_kk,
                    'nomor_rumah'   => $resident->nomor_rumah,
                    'rt'            => $resident->nomor_rt,
                    'rw'            => $resident->nomor_rw,
                    'desa'          => $resident->desa,
                    'kecamatan'     => $resident->kecamatan,
                    'kabupaten'     => $resident->kabupaten,
                ];
            });
            $data += [
                'residents' => $transformedResidents,
                'meta'      => [
                    'total'        => $residents->count(),
                    'current_page' => 1,
                    'per_page'     => $residents->count(),
                    'search'       => request('search', ''),
                    'sort'         => request('sort', ''),
                    'order'        => request('order', 'asc'),
                ],
            ];
            return $data;
        }

        $residents = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);
        $transformedResidents = $residents->getCollection()->map(function ($resident) {
            return [
                'id'            => $resident->id,
                'nik'           => $resident->nik,
                'nama'          => $resident->nama,
                'tempat_lahir'  => $resident->tempat_lahir,
                'tanggal_lahir' => $resident->tanggal_lahir,
                'jenis_kelamin' => $resident->jenis_kelamin,
                'status'        => $resident->status_name,
                'no_kk'         => $resident->no_kk,
                'nomor_rumah'   => $resident->nomor_rumah,
                'rt'            => $resident->nomor_rt,
                'rw'            => $resident->nomor_rw,
                'desa'          => $resident->desa,
                'kecamatan'     => $resident->kecamatan,
                'kabupaten'     => $resident->kabupaten,
            ];
        });
        $data += [
            'residents' => $transformedResidents,
            'meta'      => [
                'total'        => $residents->total(),
                'current_page' => $residents->currentPage(),
                'per_page'     => $residents->perPage(),
                'search'       => request('search', ''),
                'sort'         => request('sort', ''),
                'order'        => request('order', 'asc'),
            ],
        ];
        return $data;
    }

    public function customCreateEdit($data, $item = null)
    {
        $familiesRepository = app(FamiliesRepository::class);
        $families = $familiesRepository->getAll([], false, false);
        $families->load('house.rt.rw');

        $residentStatusRepository = app(ResidentStatusRepository::class);
        $statuses = $residentStatusRepository->getAll([], false, false);

        $data += [
            'listFamily' => $families->map(function ($family) {
                return [
                    'value' => $family->id,
                    'label' => $family->no_kk . ' - ' . ($family->house->nomor_rumah ?? '') . ' - RT ' . ($family->house->rt->nomor_rt ?? '') . ' - RW ' . ($family->house->rt->rw->nomor_rw ?? '') . ' - ' . ($family->house->rt->rw->desa ?? ''),
                ];
            })->toArray(),
            'listStatus' => $statuses->map(function ($status) {
                return [
                    'value' => $status->id,
                    'label' => $status->name,
                ];
            })->toArray(),
        ];

        if ($item) {
            $item->load('resident_moves', 'resident_deaths', 'status');
            if ($item->status && $item->status->code === 'PINDAH' && $item->resident_moves && $item->resident_moves->count() > 0) {
                $move = $item->resident_moves->first();
                $data['item'] = array_merge($item->toArray(), [
                    'jenis_pindah'   => $move->jenis_pindah ?? null,
                    'alamat_tujuan'  => $move->alamat_tujuan ?? null,
                    'desa'           => $move->desa ?? null,
                    'kecamatan'      => $move->kecamatan ?? null,
                    'kabupaten'      => $move->kabupaten ?? null,
                    'tanggal_pindah' => $move->tanggal_pindah ? date('Y-m-d', strtotime($move->tanggal_pindah)) : null,
                ]);
            } elseif ($item->status && $item->status->code === 'MENINGGAL' && $item->resident_deaths && $item->resident_deaths->count() > 0) {
                $death = $item->resident_deaths->first();
                $data['item'] = array_merge($item->toArray(), [
                    'tanggal_meninggal' => $death->tanggal_meninggal ? date('Y-m-d', strtotime($death->tanggal_meninggal)) : null,
                    'keterangan'        => $death->keterangan ?? null,
                ]);
            } else {
                $data['item'] = $item->toArray();
            }
        }

        return $data;
    }

    public function customShow($data, $item)
    {
        $item->load('resident_moves', 'resident_deaths');
        
        $houses = \App\Models\Houses::where('pemilik_id', $item->id)
            ->with(['rt.rw'])
            ->get();
        
        $aset = $houses->map(function ($house) {
            return [
                'id' => $house->id,
                'nomor_rumah' => $house->nomor_rumah,
                'jenis_rumah' => $house->jenis_rumah,
                'rt' => $house->rt->nomor_rt ?? '-',
                'rw' => $house->rt->rw->nomor_rw ?? '-',
                'desa' => $house->rt->rw->desa ?? '-',
            ];
        })->toArray();
        
        $fields = [
            ['label' => 'NIK', 'value' => $item->nik ?? '-'],
            ['label' => 'Nama', 'value' => $item->nama ?? '-'],
            ['label' => 'Tempat Lahir', 'value' => $item->tempat_lahir ?? '-'],
            ['label' => 'Tanggal Lahir', 'value' => $item->tanggal_lahir ? date('d-m-Y', strtotime($item->tanggal_lahir)) : '-'],
            ['label' => 'Jenis Kelamin', 'value' => ($item->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan') ?? '-'],
            ['label' => 'Status', 'value' => $item->status->name ?? '-'],
            ['label' => 'Status Note', 'value' => $item->status_note ?? '-'],
            ['label' => 'Kartu Keluarga', 'value' => ($item->family->no_kk ?? '-')],
        ];

        if ($item->status && $item->status->code === 'PINDAH' && $item->resident_moves && $item->resident_moves->count() > 0) {
            $move = $item->resident_moves->first();
            $fields[] = ['label' => 'Jenis Pindah', 'value' => $move->jenis_pindah ?? '-'];
            $fields[] = ['label' => 'Alamat Tujuan', 'value' => $move->alamat_tujuan ?? '-'];
            $fields[] = ['label' => 'Desa', 'value' => $move->desa ?? '-'];
            $fields[] = ['label' => 'Kecamatan', 'value' => $move->kecamatan ?? '-'];
            $fields[] = ['label' => 'Kabupaten', 'value' => $move->kabupaten ?? '-'];
            $fields[] = ['label' => 'Tanggal Pindah', 'value' => $move->tanggal_pindah ? date('d-m-Y', strtotime($move->tanggal_pindah)) : '-'];
        }

        if ($item->status && $item->status->code === 'MENINGGAL' && $item->resident_deaths && $item->resident_deaths->count() > 0) {
            $death = $item->resident_deaths->first();
            $fields[] = ['label' => 'Tanggal Meninggal', 'value' => $death->tanggal_meninggal ? date('d-m-Y', strtotime($death->tanggal_meninggal)) : '-'];
            $fields[] = ['label' => 'Keterangan', 'value' => $death->keterangan ?? '-'];
        }

        $actionFields = [
            ['label' => 'Created At', 'value' => $item->created_at ? date('Y-m-d H:i:s', strtotime($item->created_at)) : '-'],
            ['label' => 'Created By', 'value' => optional($item->created_by_user)->name ?? '-'],
            ['label' => 'Updated At', 'value' => $item->updated_at ? date('Y-m-d H:i:s', strtotime($item->updated_at)) : '-'],
            ['label' => 'Updated By', 'value' => optional($item->updated_by_user)->name ?? '-'],
        ];

        $data += [
            'fields'       => $fields,
            'actionFields' => $actionFields,
            'aset'         => $aset,
        ];

        return $data;
    }

    public function callbackAfterStoreOrUpdate($model, $data, $method = 'store', $record_sebelumnya = null)
    {
        $status = ResidentStatus::find($data['status_id'] ?? $model->status_id);
        
        if ($status && $status->code === 'PINDAH') {
            if ($method === 'update' && $record_sebelumnya) {
                ResidentMoves::where('resident_id', $model->id)->delete();
            }
            
            ResidentMoves::create([
                'resident_id'    => $model->id,
                'jenis_pindah'   => $data['jenis_pindah'] ?? null,
                'alamat_tujuan'  => $data['alamat_tujuan'] ?? null,
                'desa'           => $data['desa'] ?? null,
                'kecamatan'      => $data['kecamatan'] ?? null,
                'kabupaten'      => $data['kabupaten'] ?? null,
                'tanggal_pindah' => $data['tanggal_pindah'] ?? null,
                'created_by'     => auth()->id(),
                'updated_by'     => auth()->id(),
            ]);
        } else {
            ResidentMoves::where('resident_id', $model->id)->delete();
        }

        if ($status && $status->code === 'MENINGGAL') {
            if (isset($data['tanggal_meninggal'])) {
                if ($method === 'update' && $record_sebelumnya) {
                    ResidentDeaths::where('resident_id', $model->id)->delete();
                }
                
                ResidentDeaths::create([
                    'resident_id'      => $model->id,
                    'tanggal_meninggal' => $data['tanggal_meninggal'] ?? now(),
                    'keterangan'       => $data['keterangan'] ?? null,
                    'created_by'       => auth()->id(),
                    'updated_by'       => auth()->id(),
                ]);
            }
        } else {
            ResidentDeaths::where('resident_id', $model->id)->delete();
        }

        return $model;
    }
}

