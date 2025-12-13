<?php

namespace App\Repositories;

use App\Models\Rts;
use App\Traits\RepositoryTrait;
use App\Repositories\RwsRepository;

class RtsRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(Rts $model)
    {
        $this->model = $model;
        $this->with = ['rw', 'created_by_user', 'updated_by_user'];
    }

    public function customIndex($data)
    {
        $query = $this->model->with('rw')
            ->select('rts.id', 'rts.rw_id', 'rts.nomor_rt', 'rts.keterangan', 'rws.nomor_rw', 'rws.desa', 'rws.kecamatan', 'rws.kabupaten')
            ->leftJoin('rws', 'rts.rw_id', '=', 'rws.id');

        if (request('filter_rw_id')) {
            $query->where('rts.rw_id', request('filter_rw_id'));
        }

        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('rts.nomor_rt', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rts.keterangan', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rws.nomor_rw', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rws.desa', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rws.kecamatan', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rws.kabupaten', 'like', '%' . $searchTerm . '%');
            });
        }

        if (request('sort')) {
            $order = request('order', 'asc');
            $sortMapping = [
                'nomor_rt'  => 'rts.nomor_rt',
                'rw'        => 'rws.nomor_rw',
                'desa'      => 'rws.desa',
                'kecamatan' => 'rws.kecamatan',
                'kabupaten' => 'rws.kabupaten',
            ];

            $sortColumn = $sortMapping[request('sort')] ?? 'rts.id';
            $query->orderBy($sortColumn, $order);
        } else {
            $query->orderBy('rts.id', 'desc');
        }

        $perPage = (int) request('per_page', 10);
        
        if ($perPage === -1) {
            $allRts = $query->get();
            $transformedRts = $allRts->map(function ($rt) {
                return [
                    'id'         => $rt->id,
                    'nomor_rt'   => $rt->nomor_rt,
                    'rw_id'      => $rt->rw_id,
                    'rw'         => $rt->nomor_rw . ' - ' . $rt->desa,
                    'desa'       => $rt->desa,
                    'kecamatan'  => $rt->kecamatan,
                    'kabupaten'  => $rt->kabupaten,
                    'keterangan' => $rt->keterangan,
                ];
            });

            $data += [
                'rts' => $transformedRts,
                'meta' => [
                    'total'        => $transformedRts->count(),
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
        $rts            = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);

        $transformedRts = $rts->getCollection()->map(function ($rt) {
            return [
                'id'         => $rt->id,
                'nomor_rt'   => $rt->nomor_rt,
                'rw_id'      => $rt->rw_id,
                'rw'         => $rt->nomor_rw . ' - ' . $rt->desa,
                'desa'       => $rt->desa,
                'kecamatan'  => $rt->kecamatan,
                'kabupaten'  => $rt->kabupaten,
                'keterangan' => $rt->keterangan,
            ];
        });

        $data += [
            'rts' => $transformedRts,
            'meta' => [
                'total'        => $rts->total(),
                'current_page' => $rts->currentPage(),
                'per_page'     => $rts->perPage(),
                'search'       => request('search', ''),
                'sort'         => request('sort', ''),
                'order'        => request('order', 'asc'),
            ],
        ];

        return $data;
    }

    public function customCreateEdit($data, $item = null)
    {
        $rwsRepository = app(RwsRepository::class);
        $rws = $rwsRepository->getAll([], false, false);
        
        $data += [
            'listRw' => $rws->map(function ($rw) {
                return [
                    'value' => $rw->id,
                    'label' => $rw->nomor_rw . ' - ' . $rw->desa . ', ' . $rw->kecamatan . ', ' . $rw->kabupaten,
                ];
            })->toArray(),
        ];

        return $data;
    }

    public function customShow($data, $item = null)
    {
        return $data;
    }
}

