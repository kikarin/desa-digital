<?php

namespace App\Http\Controllers;

use App\Http\Requests\FamiliesRequest;
use App\Repositories\FamiliesRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Models\Residents;
use App\Repositories\RwsRepository;
use App\Repositories\RtsRepository;

class FamiliesController extends Controller implements HasMiddleware
{
    use BaseTrait;
    
    private $repository;
    private $request;

    public function __construct(FamiliesRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = FamiliesRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'families';
        $this->commonData['kode_first_menu']  = 'DATA-WARGA';
        $this->commonData['kode_second_menu'] = $this->kode_menu;
    }

    public static function middleware(): array
    {
        $className  = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));
        return [
            new Middleware("can:$permission Show", only: ['index']),
            new Middleware("can:$permission Add", only: ['create', 'store']),
            new Middleware("can:$permission Detail", only: ['show']),
            new Middleware("can:$permission Edit", only: ['edit', 'update']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
        ];
    }

    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);
        
        $rwsRepository = app(RwsRepository::class);
        $rws = $rwsRepository->getAll([], false, false);
        
        $rtsRepository = app(RtsRepository::class);
        $rts = $rtsRepository->getAll([], false, false);
        $rts->load('rw');
        
        return response()->json([
            'data' => $data['families'],
            'meta' => [
                'total'        => $data['meta']['total'],
                'current_page' => $data['meta']['current_page'],
                'per_page'     => $data['meta']['per_page'],
                'search'       => $data['meta']['search'],
                'sort'         => $data['meta']['sort'],
                'order'        => $data['meta']['order'],
            ],
            'filterOptions' => [
                'rw' => $rws->map(function ($rw) {
                    return [
                        'value' => $rw->id,
                        'label' => $rw->nomor_rw . ' - ' . $rw->desa . ', ' . $rw->kecamatan . ', ' . $rw->kabupaten,
                    ];
                })->toArray(),
                'rt' => $rts->map(function ($rt) {
                    $rw = $rt->rw;
                    $label = $rt->nomor_rt;
                    if ($rw) {
                        $label .= ' - RW ' . $rw->nomor_rw . ' - ' . $rw->desa;
                    }
                    return [
                        'value' => $rt->id,
                        'label' => $label,
                        'rw_id' => $rt->rw_id,
                    ];
                })->toArray(),
                'nomor_rumah' => true,
                'status_bantuan' => [
                    ['value' => 'received', 'label' => 'Sudah Menerima Bantuan'],
                    ['value' => 'not_received', 'label' => 'Belum Menerima Bantuan'],
                ],
            ],
        ]);
    }

    public function getResidents($id)
    {
        $family = $this->repository->getById($id);
        $family->load('house.families.residents.status');

        $residents = [];
        foreach ($family->house->families as $fam) {
            foreach ($fam->residents as $resident) {
                $residents[] = [
                    'id' => $resident->id,
                    'nik' => $resident->nik,
                    'nama' => $resident->nama,
                    'status' => $resident->status->name ?? '-',
                ];
            }
        }

        return response()->json(['data' => $residents]);
    }

    public function apiShow($id)
    {
        $item = $this->repository->getById($id);
        if (!$item) {
            return response()->json(['error' => 'Family not found'], 404);
        }

        $data = $this->commonData + [
            'item' => $item,
        ];
        $data = $this->repository->customShow($data, $item);
        
        // Convert item to array untuk JSON response
        if (isset($data['item']) && is_object($data['item'])) {
            $data['item'] = $data['item']->toArray();
        }
        
        return response()->json($data);
    }

    public function setKepalaKeluarga(Request $request, $id)
    {
        $request->validate([
            'kepala_keluarga_id' => 'required|exists:residents,id',
        ]);

        $family = $this->repository->getById($id);

        $resident = Residents::find($request->kepala_keluarga_id);
        if ($resident->family->house_id !== $family->house_id) {
            return response()->json([
                'message' => 'Warga harus dari rumah yang sama'
            ], 422);
        }

        $family->kepala_keluarga_id = $request->kepala_keluarga_id;
        $family->save();

        return response()->json([
            'message' => 'Kepala keluarga berhasil diupdate'
        ]);
    }
}

