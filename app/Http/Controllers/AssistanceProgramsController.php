<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssistanceProgramsRequest;
use App\Repositories\AssistanceProgramsRepository;
use App\Repositories\AssistanceRecipientsRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Models\AssistanceProgram;
use Illuminate\Support\Facades\Auth;

class AssistanceProgramsController extends Controller implements HasMiddleware
{
    use BaseTrait;
    
    private $repository;
    private $request;

    /**
     * Constructor - inject repository dan request
     */
    public function __construct(AssistanceProgramsRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = AssistanceProgramsRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'assistance-programs';
        $this->commonData['kode_first_menu']  = 'PROGRAM-BANTUAN';
        $this->commonData['kode_second_menu'] = $this->kode_menu;
    }

    /**
     * Middleware untuk permission checking
     */
    public static function middleware(): array
    {
        $className  = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));
        return [
            new Middleware("can:$permission Show", only: ['index', 'apiIndex']),
            new Middleware("can:$permission Add", only: ['create', 'store']),
            new Middleware("can:$permission Detail", only: ['show']),
            new Middleware("can:$permission Edit", only: ['edit', 'update']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
            new Middleware("can:Assistance Programs penyaluran", only: ['distribution', 'apiDistribution', 'getFamilyResidents', 'updateDistribution']),
        ];
    }

    /**
     * Override getPermission untuk menambahkan permission Penyaluran
     */
    private function getPermission()
    {
        $auth_user = Auth::user();
        $permission_main = $this->permission_main;
        return [
            'can' => [
                'Add'        => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($permission_main . ' Add') : false,
                'Edit'       => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($permission_main . ' Edit') : false,
                'Delete'     => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($permission_main . ' Delete') : false,
                'Detail'     => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($permission_main . ' Detail') : false,
                'Penyaluran' => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can('Assistance Programs penyaluran') : false,
            ],
        ];
    }

    /**
     * API endpoint untuk data table
     */
    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);
        return response()->json([
            'data' => $data['assistance_programs'],
            'meta' => [
                'total'        => $data['meta']['total'],
                'current_page' => $data['meta']['current_page'],
                'per_page'     => $data['meta']['per_page'],
                'search'       => $data['meta']['search'],
                'sort'         => $data['meta']['sort'],
                'order'        => $data['meta']['order'],
            ],
        ]);
    }

    /**
     * Show distribution page
     */
    public function distribution(Request $request)
    {
        $programId = $request->query('program_id');
        
        if (!$programId) {
            return redirect()->route('assistance-programs.index')->with('error', 'Program ID tidak ditemukan');
        }

        $program = AssistanceProgram::with(['program_items.item'])->find($programId);
        if (!$program) {
            return redirect()->route('assistance-programs.index')->with('error', 'Program tidak ditemukan');
        }

        // Load filter options
        $rwsRepository = app(\App\Repositories\RwsRepository::class);
        $rws = $rwsRepository->getAll([], false, false);
        
        $rtsRepository = app(\App\Repositories\RtsRepository::class);
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
            'status' => [
                ['value' => 'PROSES', 'label' => 'PROSES'],
                ['value' => 'DATANG', 'label' => 'DATANG'],
                ['value' => 'TIDAK_DATANG', 'label' => 'TIDAK DATANG'],
            ],
        ];

        return inertia('modules/assistance-recipients/Distribution', [
            'program' => [
                'id' => $program->id,
                'nama_program' => $program->nama_program,
                'tahun' => $program->tahun,
                'periode' => $program->periode,
                'target_penerima' => $program->target_penerima,
                'items' => $program->program_items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'nama_item' => $item->item->nama_item ?? '-',
                        'jumlah' => $item->jumlah,
                        'satuan' => $item->item->satuan ?? '-',
                        'tipe' => $item->item->tipe ?? '-',
                    ];
                }),
            ],
            'filterOptions' => $filterOptions,
        ]);
    }

    /**
     * API endpoint untuk distribution index
     */
    public function apiDistribution(Request $request)
    {
        $recipientsRepository = app(AssistanceRecipientsRepository::class);
        $data = $recipientsRepository->getDistributionData($request->all());
        
        $rwsRepository = app(\App\Repositories\RwsRepository::class);
        $rws = $rwsRepository->getAll([], false, false);
        
        $rtsRepository = app(\App\Repositories\RtsRepository::class);
        $rts = $rtsRepository->getAll([], false, false);
        $rts->load('rw');

        return response()->json([
            'data' => $data['assistance_recipients'],
            'meta' => $data['meta'],
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
                'status' => [
                    ['value' => 'PROSES', 'label' => 'PROSES'],
                    ['value' => 'DATANG', 'label' => 'DATANG'],
                    ['value' => 'TIDAK_DATANG', 'label' => 'TIDAK DATANG'],
                ],
            ],
        ]);
    }

    /**
     * Get family residents untuk dropdown perwakilan
     */
    public function getFamilyResidents($id)
    {
        $recipientsRepository = app(AssistanceRecipientsRepository::class);
        $recipient = $recipientsRepository->getById($id);
        if (!$recipient) {
            return response()->json(['data' => []], 404);
        }

        $residents = $recipientsRepository->getFamilyResidents($recipient);
        
        return response()->json([
            'data' => $residents,
        ]);
    }

    /**
     * Update distribution status
     */
    public function updateDistribution(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:DATANG,TIDAK_DATANG',
            'tanggal_penyaluran' => 'required_if:status,DATANG|nullable|date',
            'penerima_lapangan_id' => 'nullable|exists:residents,id',
            'catatan' => 'nullable|string|max:1000',
        ]);

        try {
            $recipientsRepository = app(AssistanceRecipientsRepository::class);
            $recipient = $recipientsRepository->updateDistribution($id, $validated);
            
            return response()->json([
                'message' => 'Status penyaluran berhasil diperbarui',
                'data' => $recipient,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

