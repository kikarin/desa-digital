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
            new Middleware("can:Families Bulk Assign Desil", only: ['bulkAssignDesil', 'storeBulkAssignDesil', 'getFamiliesWithoutDesil']),
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

    /**
     * Show bulk assign desil page
     */
    public function bulkAssignDesil(Request $request)
    {
        // Load filter options
        $rwsRepository = app(RwsRepository::class);
        $rws = $rwsRepository->getAll([], false, false);
        
        $rtsRepository = app(RtsRepository::class);
        $rts = $rtsRepository->getAll([], false, false);
        $rts->load('rw');

        $filterOptions = [
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
        ];

        return inertia('modules/families/BulkAssignDesil', [
            'filterOptions' => $filterOptions,
        ]);
    }

    /**
     * API endpoint untuk get families tanpa desil
     */
    public function getFamiliesWithoutDesil(Request $request)
    {
        $filters = [
            'rw_id'  => $request->filter_rw_id,
            'rt_id'  => $request->filter_rt_id,
            'search' => $request->search,
        ];

        $query = $this->repository->getFamiliesWithoutDesil($filters);

        // Pagination
        $perPage = (int) $request->per_page ?? 10;
        $page = (int) $request->page ?? 0;
        $pageForLaravel = $page < 1 ? 1 : $page + 1;

        if ($perPage === -1) {
            $families = $query->get();
        } else {
            $families = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);
        }

        $transformed = $families->getCollection()->map(function ($family) {
            $kepalaKeluarga = $family->kepala_keluarga ?? null;
            $jumlahAnggota = $family->residents ? $family->residents->count() : 0;

            return [
                'id'                    => $family->id,
                'no_kk'                 => $family->no_kk,
                'kepala_keluarga_id'    => $family->kepala_keluarga_id,
                'kepala_keluarga_nama'  => $kepalaKeluarga ? $kepalaKeluarga->nama : '-',
                'nomor_rumah'           => $family->nomor_rumah,
                'rt'                    => $family->nomor_rt,
                'rw'                    => $family->nomor_rw,
                'alamat'                => ($family->nomor_rumah ? $family->nomor_rumah . ', ' : '') . 
                                          'RT ' . ($family->nomor_rt ?? '-') . ', RW ' . ($family->nomor_rw ?? '-') . 
                                          ($family->desa ? ', ' . $family->desa : ''),
                'jumlah_anggota'        => $jumlahAnggota,
            ];
        });

        if ($perPage === -1) {
            return response()->json([
                'data' => $transformed,
                'meta' => [
                    'total'        => $transformed->count(),
                    'current_page' => 1,
                    'per_page'     => -1,
                ],
            ]);
        }

        return response()->json([
            'data' => $transformed,
            'meta' => [
                'total'        => $families->total(),
                'current_page' => $families->currentPage(),
                'per_page'     => $families->perPage(),
            ],
        ]);
    }

    /**
     * Store bulk assign desil
     */
    public function storeBulkAssignDesil(Request $request)
    {
        $request->validate([
            'desil'      => 'required|integer|min:1|max:10',
            'family_ids' => 'required|array|min:1',
            'family_ids.*' => 'exists:families,id',
        ]);

        try {
            $desil = $request->desil;
            $familyIds = $request->family_ids;

            // Update desil untuk semua keluarga yang dipilih
            \App\Models\Families::whereIn('id', $familyIds)
                ->update(['desil' => $desil]);

            return redirect()->route('families.index')
                ->with('success', count($familyIds) . ' keluarga berhasil diassign desil ' . $desil);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal assign desil: ' . $e->getMessage())
                ->withInput();
        }
    }
}

