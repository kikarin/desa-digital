<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssistanceRecipientsRequest;
use App\Repositories\AssistanceRecipientsRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use App\Models\AssistanceProgram;
use App\Repositories\RwsRepository;
use App\Repositories\RtsRepository;

class AssistanceRecipientsController extends Controller implements HasMiddleware
{
    use BaseTrait;
    
    private $repository;
    private $request;

    /**
     * Constructor - inject repository dan request
     */
    public function __construct(AssistanceRecipientsRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = AssistanceRecipientsRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'assistance-recipients';
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
            new Middleware("can:$permission Show", only: ['index', 'apiIndex', 'getAvailableFamilies', 'getAvailableResidents']),
            new Middleware("can:$permission Add", only: ['create', 'store', 'storeMultiple']),
            new Middleware("can:$permission Detail", only: ['show']),
            new Middleware("can:$permission Edit", only: ['edit', 'update']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
        ];
    }

    /**
     * Show create multiple page
     */
    public function createMultiple(Request $request)
    {
        $programId = $request->query('program_id');
        
        if (!$programId) {
            return redirect()->route('assistance-recipients.index')->with('error', 'Program ID tidak ditemukan');
        }

        $program = AssistanceProgram::find($programId);
        if (!$program) {
            return redirect()->route('assistance-recipients.index')->with('error', 'Program tidak ditemukan');
        }

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

        // Jika target_type = INDIVIDU, tambahkan filter jenis kelamin
        if ($program->target_penerima === 'INDIVIDU') {
            $filterOptions['jenis_kelamin'] = [
                ['value' => 'L', 'label' => 'Laki-laki'],
                ['value' => 'P', 'label' => 'Perempuan'],
            ];
        }

        return inertia('modules/assistance-recipients/CreateMultiple', [
            'program' => [
                'id' => $program->id,
                'nama_program' => $program->nama_program,
                'tahun' => $program->tahun,
                'periode' => $program->periode,
                'target_penerima' => $program->target_penerima,
            ],
            'filterOptions' => $filterOptions,
        ]);
    }

    /**
     * Override store method untuk handle error dengan message yang user-friendly
     */
    public function store(Request $request)
    {
        $this->request = AssistanceRecipientsRequest::createFromBase($request);
        $this->repository->customProperty(__FUNCTION__);
        
        try {
            $data = $this->request->validate($this->getValidationRules());
            $data = $this->request->all();
            $before = $this->repository->callbackBeforeStoreOrUpdate($data, 'store');
            if ($before['error'] != 0) {
                return redirect()->back()->with('error', $before['message'])->withInput();
            } else {
                $data = $before['data'];
            }
            $model = $this->repository->create($data);
            if (!($model instanceof \Illuminate\Database\Eloquent\Model)) {
                return $model;
            }
            
            // Redirect dengan preserve program_id
            $redirectUrl = '/program-bantuan/penerima';
            if ($data['assistance_program_id']) {
                $redirectUrl .= '?program_id=' . $data['assistance_program_id'];
            }
            return redirect($redirectUrl)->with('success', trans('message.success_add'));
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            
            if (str_contains($message, 'sudah terdaftar')) {
                throw ValidationException::withMessages([
                    $this->request->target_type === 'KELUARGA' ? 'family_id' : 'resident_id' => $message,
                ]);
            }
            
            if (str_contains($message, 'Duplicate entry') || str_contains($message, 'unique_program')) {
                throw ValidationException::withMessages([
                    $this->request->target_type === 'KELUARGA' ? 'family_id' : 'resident_id' => 'Penerima ini sudah terdaftar di program ini.',
                ]);
            }
            
            throw $e;
        }
    }

    /**
     * Store multiple recipients
     */
    public function storeMultiple(Request $request)
    {
        $request->validate([
            'program_id'  => 'required|exists:assistance_programs,id',
            'target_type' => 'required|in:KELUARGA,INDIVIDU',
            'recipients'  => 'required|array|min:1',
        ]);

        try {
            $program = AssistanceProgram::find($request->program_id);
            if (!$program) {
                throw new \Exception('Program tidak ditemukan');
            }

            // Prepare recipients data
            $recipients = [];
            foreach ($request->recipients as $recipient) {
                if ($request->target_type === 'KELUARGA') {
                    $recipients[] = ['family_id' => $recipient['family_id']];
                } else {
                    $recipients[] = ['resident_id' => $recipient['resident_id']];
                }
            }

            $result = $this->repository->storeMultiple($request->program_id, $recipients, $request->target_type);

            if (!empty($result['errors'])) {
                $errorMessages = array_map(function ($error) {
                    return $error['message'];
                }, $result['errors']);
                
                return redirect()->back()->with('error', 'Beberapa penerima gagal ditambahkan: ' . implode(', ', $errorMessages))->withInput();
            }

            $redirectUrl = '/program-bantuan/penerima?program_id=' . $request->program_id;
            return redirect($redirectUrl)->with('success', count($result['created']) . ' penerima berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan penerima: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * API endpoint untuk data table
     */
    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);
        
        // Load filter options untuk RT/RW
        $rwsRepository = app(RwsRepository::class);
        $rws = $rwsRepository->getAll([], false, false);
        
        $rtsRepository = app(RtsRepository::class);
        $rts = $rtsRepository->getAll([], false, false);
        $rts->load('rw');
        
        return response()->json([
            'data' => $data['assistance_recipients'],
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
            ],
        ]);
    }

    /**
     * API endpoint untuk get available families (untuk create multiple)
     */
    public function getAvailableFamilies(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:assistance_programs,id',
        ]);

        $filters = [
            'rw_id'  => $request->filter_rw_id,
            'rt_id'  => $request->filter_rt_id,
            'search' => $request->search,
        ];

        $query = $this->repository->getAvailableFamilies($request->program_id, $filters);

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
                'pernah_dapat_bantuan'  => (int) ($family->pernah_dapat_bantuan ?? 0) > 0,
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
     * API endpoint untuk get available residents (untuk create multiple)
     */
    public function getAvailableResidents(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:assistance_programs,id',
        ]);

        $filters = [
            'rw_id'         => $request->filter_rw_id,
            'rt_id'         => $request->filter_rt_id,
            'jenis_kelamin' => $request->filter_jenis_kelamin,
            'search'        => $request->search,
        ];

        $query = $this->repository->getAvailableResidents($request->program_id, $filters);

        // Pagination
        $perPage = (int) $request->per_page ?? 10;
        $page = (int) $request->page ?? 0;
        $pageForLaravel = $page < 1 ? 1 : $page + 1;

        if ($perPage === -1) {
            $residents = $query->get();
        } else {
            $residents = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);
        }

        $transformed = $residents->getCollection()->map(function ($resident) {
            return [
                'id'                    => $resident->id,
                'nik'                   => $resident->nik,
                'nama'                  => $resident->nama,
                'jenis_kelamin'         => $resident->jenis_kelamin,
                'jenis_kelamin_label'   => $resident->jenis_kelamin === 'L' ? 'Laki-laki' : ($resident->jenis_kelamin === 'P' ? 'Perempuan' : '-'),
                'tanggal_lahir'         => $resident->tanggal_lahir,
                'usia'                  => (int) ($resident->usia ?? 0),
                'status_id'             => $resident->status_id,
                'status_name'           => $resident->status_name ?? '-',
                'no_kk'                 => $resident->no_kk,
                'nomor_rumah'           => $resident->nomor_rumah,
                'rt'                    => $resident->nomor_rt,
                'rw'                    => $resident->nomor_rw,
                'alamat'                => ($resident->nomor_rumah ? $resident->nomor_rumah . ', ' : '') . 
                                          'RT ' . ($resident->nomor_rt ?? '-') . ', RW ' . ($resident->nomor_rw ?? '-') . 
                                          ($resident->desa ? ', ' . $resident->desa : ''),
                'pernah_dapat_bantuan'  => (int) ($resident->pernah_dapat_bantuan ?? 0) > 0,
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
                'total'        => $residents->total(),
                'current_page' => $residents->currentPage(),
                'per_page'     => $residents->perPage(),
            ],
        ]);
    }
}

