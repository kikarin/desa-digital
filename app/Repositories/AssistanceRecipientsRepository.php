<?php

namespace App\Repositories;

use App\Models\AssistanceRecipient;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\AssistanceProgram;
use App\Models\Families;
use App\Models\Residents;
use App\Repositories\AssistanceProgramsRepository;
use App\Repositories\FamiliesRepository;
use App\Repositories\ResidentsRepository;
use App\Repositories\ResidentStatusRepository;
use App\Models\ResidentStatus;

class AssistanceRecipientsRepository
{
    use RepositoryTrait;

    protected $model;

    /**
     * Constructor - set model dan relasi yang akan di-load
     */
    public function __construct(AssistanceRecipient $model)
    {
        $this->model = $model;
        // Load relasi program, family, resident, dan user untuk tracking
        $this->with = ['program', 'family', 'resident', 'kepala_keluarga', 'penerima_lapangan', 'created_by_user', 'updated_by_user'];
    }

    /**
     * Custom data untuk halaman index
     * Handle search, sort, dan pagination
     */
    public function customIndex($data)
    {
        $query = $this->model->with([
            'program', 
            'family.house.rt.rw', 
            'family.kepala_keluarga',
            'resident.family.house.rt.rw',
            'kepala_keluarga',
            'penerima_lapangan'
        ])
            ->whereNull('assistance_recipients.deleted_at'); // Abaikan soft-deleted records

        // Filter berdasarkan program_id - check dari filter_program_id atau program_id query parameter
        $programId = request('filter_program_id') ?? request('program_id');
        if ($programId) {
            $query->where('assistance_recipients.assistance_program_id', $programId);
        }

        // Filter berdasarkan target_type
        if (request('filter_target_type')) {
            $query->where('assistance_recipients.target_type', request('filter_target_type'));
        }

        // Filter berdasarkan status
        if (request('filter_status')) {
            $query->where('assistance_recipients.status', request('filter_status'));
        }

        // Filter berdasarkan RT/RW
        // Untuk filter RT/RW, perlu join dengan families/residents -> house -> rt -> rw
        if (request('filter_rw_id') || request('filter_rt_id')) {
            $query->where(function ($q) {
                // Filter untuk KELUARGA
                $q->where(function ($q2) {
                    $q2->where('assistance_recipients.target_type', 'KELUARGA')
                        ->whereHas('family.house.rt.rw', function ($q3) {
                            if (request('filter_rw_id')) {
                                $q3->where('rws.id', request('filter_rw_id'));
                            }
                            if (request('filter_rt_id')) {
                                $q3->where('rts.id', request('filter_rt_id'));
                            }
                        });
                })
                // Filter untuk INDIVIDU
                ->orWhere(function ($q2) {
                    $q2->where('assistance_recipients.target_type', 'INDIVIDU')
                        ->whereHas('resident.family.house.rt.rw', function ($q3) {
                            if (request('filter_rw_id')) {
                                $q3->where('rws.id', request('filter_rw_id'));
                            }
                            if (request('filter_rt_id')) {
                                $q3->where('rts.id', request('filter_rt_id'));
                            }
                        });
                });
            });
        }

        // Cari data berdasarkan keyword
        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('assistance_programs.nama_program', 'like', '%' . $searchTerm . '%')
                    ->orWhere('assistance_programs.periode', 'like', '%' . $searchTerm . '%');
            });
        }

        // Sorting data
        if (request('sort')) {
            $order = request('order', 'asc');
            $sortMapping = [
                'nama_program' => 'assistance_programs.nama_program',
                'target_type'  => 'assistance_recipients.target_type',
                'status'       => 'assistance_recipients.status',
            ];

            $sortColumn = $sortMapping[request('sort')] ?? 'assistance_recipients.id';
            $query->orderBy($sortColumn, $order);
        } else {
            $query->orderBy('assistance_recipients.id', 'desc'); // Default sort by id desc
        }

        // Pagination
        $perPage = (int) request('per_page', 10);
        
        // Kalau per_page = -1, ambil semua data
        if ($perPage === -1) {
            $allRecipients = $query->get();
            $transformedRecipients = $allRecipients->map(function ($recipient) {
                return $this->transformRecipient($recipient);
            });

            $data += [
                'assistance_recipients' => $transformedRecipients,
                'meta' => [
                    'total'        => $transformedRecipients->count(),
                    'current_page' => 1,
                    'per_page'     => -1,
                    'search'       => request('search', ''),
                    'sort'         => request('sort', ''),
                    'order'        => request('order', 'asc'),
                ],
            ];
            return $data;
        }

        // Pagination normal
        $page           = (int) request('page', 0);
        $pageForLaravel = $page < 1 ? 1 : $page + 1;
        $recipients     = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);

        // Transform data untuk frontend
        $transformedRecipients = $recipients->getCollection()->map(function ($recipient) {
            return $this->transformRecipient($recipient);
        });

        $data += [
            'assistance_recipients' => $transformedRecipients,
            'meta' => [
                'total'        => $recipients->total(),
                'current_page' => $recipients->currentPage(),
                'per_page'     => $recipients->perPage(),
                'search'       => request('search', ''),
                'sort'         => request('sort', ''),
                'order'        => request('order', 'asc'),
            ],
        ];

        return $data;
    }

    /**
     * Transform recipient data untuk frontend
     */
    private function transformRecipient($recipient)
    {
        $data = [
            'id'                    => $recipient->id,
            'assistance_program_id' => $recipient->assistance_program_id,
            'target_type'           => $recipient->target_type,
            'family_id'             => $recipient->family_id,
            'resident_id'           => $recipient->resident_id,
            'kepala_keluarga_id'    => $recipient->kepala_keluarga_id,
            'status'                => $recipient->status,
            'tanggal_penyaluran'    => $recipient->tanggal_penyaluran,
            'nama_program'          => $recipient->program ? $recipient->program->nama_program : null,
            'tahun'                 => $recipient->program ? $recipient->program->tahun : null,
            'periode'               => $recipient->program ? $recipient->program->periode : null,
        ];

        // Load relasi untuk detail
        if ($recipient->target_type === 'KELUARGA' && $recipient->family) {
            $house = $recipient->family->house ?? null;
            $rt = $house->rt ?? null;
            $rw = $rt->rw ?? null;
            
            $data['family'] = [
                'id'                => $recipient->family->id,
                'no_kk'             => $recipient->family->no_kk,
                'kepala_keluarga'   => $recipient->family->kepala_keluarga ? [
                    'id'   => $recipient->family->kepala_keluarga->id,
                    'nama' => $recipient->family->kepala_keluarga->nama,
                ] : null,
                'rt'                => $rt ? $rt->nomor_rt : null,
                'rw'                => $rw ? $rw->nomor_rw : null,
            ];
        }

        if ($recipient->target_type === 'INDIVIDU' && $recipient->resident) {
            $family = $recipient->resident->family ?? null;
            $house = $family ? $family->house : null;
            $rt = $house ? $house->rt : null;
            $rw = $rt ? $rt->rw : null;
            
            $data['resident'] = [
                'id'            => $recipient->resident->id,
                'nik'           => $recipient->resident->nik,
                'nama'          => $recipient->resident->nama,
                'jenis_kelamin' => $recipient->resident->jenis_kelamin,
                'tanggal_lahir' => $recipient->resident->tanggal_lahir,
                'rt'            => $rt ? $rt->nomor_rt : null,
                'rw'            => $rw ? $rw->nomor_rw : null,
            ];
        }
        
        // Tambahkan kepala keluarga dari relasi kepala_keluarga
        if ($recipient->kepala_keluarga) {
            $data['kepala_keluarga'] = [
                'id'   => $recipient->kepala_keluarga->id,
                'nama' => $recipient->kepala_keluarga->nama,
            ];
        }

        // Tambahkan perwakilan dari relasi penerima_lapangan
        if ($recipient->penerima_lapangan) {
            $data['penerima_lapangan'] = [
                'id'   => $recipient->penerima_lapangan->id,
                'nama' => $recipient->penerima_lapangan->nama,
            ];
        }

        return $data;
    }

    /**
     * Custom data untuk halaman create/edit
     */
    public function customCreateEdit($data, $item = null)
    {
        // Load list program untuk dropdown (jika diperlukan)
        $programRepository = app(AssistanceProgramsRepository::class);
        $programs = $programRepository->getAll([], false, false);
        
        $data['list_programs'] = $programs->map(function ($program) {
            return [
                'value' => $program->id,
                'label' => $program->nama_program . ' (' . $program->tahun . ' - ' . $program->periode . ')',
            ];
        })->toArray();
        
        return $data;
    }

    /**
     * Custom data untuk halaman show/detail
     */
    public function customShow($data, $item = null)
    {
        if ($item) {
            // Load relasi lengkap
            $item->load(['program', 'family.house.rt.rw', 'resident.family.house.rt.rw', 'kepala_keluarga', 'penerima_lapangan']);
            
            // Tambahkan data tambahan ke item
            $data['item'] = array_merge($item->toArray(), [
                'nama_program' => $item->program ? $item->program->nama_program : null,
            ]);
        }
        
        return $data;
    }

    /**
     * Override create method untuk handle auto-fill kepala_keluarga_id
     */
    public function create($data)
    {
        try {
            DB::beginTransaction();
            
            // Auto-fill kepala_keluarga_id berdasarkan target_type
            if ($data['target_type'] === 'KELUARGA' && isset($data['family_id'])) {
                // Ambil kepala_keluarga_id dari family
                $family = Families::find($data['family_id']);
                if ($family && $family->kepala_keluarga_id) {
                    $data['kepala_keluarga_id'] = $family->kepala_keluarga_id;
                }
            } elseif ($data['target_type'] === 'INDIVIDU' && isset($data['resident_id'])) {
                // Ambil kepala_keluarga_id dari resident->family (opsional)
                $resident = Residents::with('family')->find($data['resident_id']);
                if ($resident && $resident->family && $resident->family->kepala_keluarga_id) {
                    $data['kepala_keluarga_id'] = $resident->family->kepala_keluarga_id;
                }
            }

            // Set status berdasarkan program status
            if (!isset($data['status'])) {
                $program = AssistanceProgram::find($data['assistance_program_id']);
                $data['status'] = $program && $program->status === 'SELESAI' ? 'SELESAI' : 'PROSES';
            }
            
            $data = $this->customDataCreateUpdate($data);
            $record = $this->model::create($data);
            $record = $this->callbackAfterStoreOrUpdate($record, $data);
            DB::commit();
            return $record;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Store multiple recipients
     */
    public function storeMultiple($programId, $recipients, $targetType)
    {
        try {
            DB::beginTransaction();
            
            $program = AssistanceProgram::find($programId);
            if (!$program) {
                throw new \Exception('Program tidak ditemukan');
            }

            $created = [];
            $errors = [];

            foreach ($recipients as $recipientData) {
                try {
                    // Check apakah ada soft-deleted record dengan kombinasi yang sama
                    $softDeleted = null;
                    if ($targetType === 'KELUARGA' && isset($recipientData['family_id'])) {
                        $softDeleted = $this->model::withTrashed()
                            ->where('assistance_program_id', $programId)
                            ->where('target_type', $targetType)
                            ->where('family_id', $recipientData['family_id'])
                            ->whereNotNull('deleted_at')
                            ->first();
                    } elseif ($targetType === 'INDIVIDU' && isset($recipientData['resident_id'])) {
                        $softDeleted = $this->model::withTrashed()
                            ->where('assistance_program_id', $programId)
                            ->where('target_type', $targetType)
                            ->where('resident_id', $recipientData['resident_id'])
                            ->whereNotNull('deleted_at')
                            ->first();
                    }

                    // Jika ada soft-deleted record, restore dulu
                    if ($softDeleted) {
                        $softDeleted->restore();
                        // Update data yang diperlukan - penerima baru selalu PROSES
                        $softDeleted->status = 'PROSES';
                        $softDeleted->save();
                        $created[] = $softDeleted;
                        continue; // Skip create, karena sudah di-restore
                    }

                    // Set status berdasarkan program status
                    // Jika program SELESAI, penerima langsung DATANG (bukan SELESAI)
                    $program = AssistanceProgram::find($programId);
                    $status = $program && $program->status === 'SELESAI' ? 'DATANG' : 'PROSES';
                    
                    $data = [
                        'assistance_program_id' => $programId,
                        'target_type'           => $targetType,
                        'status'                => $status, // PROSES atau DATANG (jika program SELESAI)
                    ];
                    
                    // Jika status DATANG, set tanggal_penyaluran
                    if ($status === 'DATANG') {
                        $data['tanggal_penyaluran'] = now();
                    }

                    if ($targetType === 'KELUARGA') {
                        $data['family_id'] = $recipientData['family_id'];
                        $data['resident_id'] = null;
                        
                        // Auto-fill kepala_keluarga_id
                        if (isset($recipientData['family_id'])) {
                            $family = Families::find($recipientData['family_id']);
                            if ($family && $family->kepala_keluarga_id) {
                                $data['kepala_keluarga_id'] = $family->kepala_keluarga_id;
                            }
                        }
                    } else {
                        $data['resident_id'] = $recipientData['resident_id'];
                        $data['family_id'] = null;
                        
                        // Auto-fill kepala_keluarga_id (opsional)
                        if (isset($recipientData['resident_id'])) {
                            $resident = Residents::with('family')->find($recipientData['resident_id']);
                            if ($resident && $resident->family && $resident->family->kepala_keluarga_id) {
                                $data['kepala_keluarga_id'] = $resident->family->kepala_keluarga_id;
                            }
                        }
                    }

                    $data = $this->customDataCreateUpdate($data);
                    $record = $this->model::create($data);
                    $created[] = $record;
                } catch (\Exception $e) {
                    $errors[] = [
                        'data'    => $recipientData,
                        'message' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();
            
            return [
                'created' => $created,
                'errors'  => $errors,
            ];
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get available families for selection (exclude yang sudah terdaftar)
     */
    public function getAvailableFamilies($programId, $filters = [])
    {
        $familiesRepo = app(FamiliesRepository::class);
        
        // Get families yang sudah terdaftar di program ini (hanya yang tidak soft-deleted)
        $existingFamilyIds = $this->model::where('assistance_program_id', $programId)
            ->where('target_type', 'KELUARGA')
            ->whereNotNull('family_id')
            ->whereNull('deleted_at') // Hanya yang tidak soft-deleted
            ->pluck('family_id')
            ->toArray();

        // Build query untuk families
        $query = Families::with('house.rt.rw', 'kepala_keluarga', 'residents')
            ->select(
                'families.id',
                'families.no_kk',
                'families.kepala_keluarga_id',
                'families.status',
                'houses.nomor_rumah',
                'rts.nomor_rt',
                'rws.nomor_rw',
                'rws.desa',
                'rws.kecamatan',
                'rws.kabupaten'
            )
            ->leftJoin('houses', 'families.house_id', '=', 'houses.id')
            ->leftJoin('rts', 'houses.rt_id', '=', 'rts.id')
            ->leftJoin('rws', 'rts.rw_id', '=', 'rws.id')
            ->where('families.status', 'AKTIF') // Hanya yang aktif
            ->whereNotIn('families.id', $existingFamilyIds); // Exclude yang sudah terdaftar

        // Filter RT/RW
        if (isset($filters['rw_id'])) {
            $query->where('rws.id', $filters['rw_id']);
        }

        if (isset($filters['rt_id'])) {
            $query->where('rts.id', $filters['rt_id']);
        }

        // Search
        if (isset($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('families.no_kk', 'like', '%' . $searchTerm . '%')
                    ->orWhere('houses.nomor_rumah', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rts.nomor_rt', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rws.nomor_rw', 'like', '%' . $searchTerm . '%');
            });
        }

        // Check apakah pernah dapat bantuan (untuk indikator)
        $query->selectRaw('(
            SELECT COUNT(*) 
            FROM assistance_recipients ar 
            WHERE (ar.family_id = families.id OR ar.resident_id IN (
                SELECT id FROM residents WHERE family_id = families.id
            ))
            AND ar.deleted_at IS NULL
        ) as pernah_dapat_bantuan');

        return $query;
    }

    /**
     * Get available residents for selection (exclude yang sudah terdaftar)
     */
    public function getAvailableResidents($programId, $filters = [])
    {
        // Get residents yang sudah terdaftar di program ini (hanya yang tidak soft-deleted)
        $existingResidentIds = $this->model::where('assistance_program_id', $programId)
            ->where('target_type', 'INDIVIDU')
            ->whereNotNull('resident_id')
            ->whereNull('deleted_at') // Hanya yang tidak soft-deleted
            ->pluck('resident_id')
            ->toArray();

        // Build query untuk residents
        $query = Residents::with('family.house.rt.rw', 'status')
            ->select(
                'residents.id',
                'residents.nik',
                'residents.nama',
                'residents.tanggal_lahir',
                'residents.jenis_kelamin',
                'residents.status_id',
                'families.no_kk',
                'houses.nomor_rumah',
                'rts.nomor_rt',
                'rws.nomor_rw',
                'rws.desa',
                'rws.kecamatan',
                'rws.kabupaten',
                'resident_statuses.name as status_name'
            )
            ->leftJoin('families', 'residents.family_id', '=', 'families.id')
            ->leftJoin('houses', 'families.house_id', '=', 'houses.id')
            ->leftJoin('rts', 'houses.rt_id', '=', 'rts.id')
            ->leftJoin('rws', 'rts.rw_id', '=', 'rws.id')
            ->leftJoin('resident_statuses', 'residents.status_id', '=', 'resident_statuses.id')
            ->where('residents.status_id', '!=', null) // Hanya yang aktif (asumsi status_id null = nonaktif)
            ->whereNotIn('residents.id', $existingResidentIds); // Exclude yang sudah terdaftar

        // Filter RT/RW
        if (isset($filters['rw_id'])) {
            $query->where('rws.id', $filters['rw_id']);
        }

        if (isset($filters['rt_id'])) {
            $query->where('rts.id', $filters['rt_id']);
        }

        // Filter jenis kelamin
        if (isset($filters['jenis_kelamin'])) {
            $query->where('residents.jenis_kelamin', $filters['jenis_kelamin']);
        }

        // Search
        if (isset($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('residents.nik', 'like', '%' . $searchTerm . '%')
                    ->orWhere('residents.nama', 'like', '%' . $searchTerm . '%')
                    ->orWhere('families.no_kk', 'like', '%' . $searchTerm . '%')
                    ->orWhere('houses.nomor_rumah', 'like', '%' . $searchTerm . '%');
            });
        }

        // Check apakah pernah dapat bantuan (untuk indikator)
        $query->selectRaw('(
            SELECT COUNT(*) 
            FROM assistance_recipients ar 
            WHERE ar.resident_id = residents.id
            AND ar.deleted_at IS NULL
        ) as pernah_dapat_bantuan');

        // Calculate usia
        $query->selectRaw('TIMESTAMPDIFF(YEAR, residents.tanggal_lahir, CURDATE()) as usia');

        return $query;
    }

    /**
     * Get distribution data untuk halaman penyaluran
     */
    public function getDistributionData($data = [])
    {
        $query = $this->model->with([
            'program', 
            'family.house.rt.rw', 
            'family.kepala_keluarga',
            'resident.family.house.rt.rw',
            'kepala_keluarga',
            'penerima_lapangan'
        ])
            ->select(
                'assistance_recipients.id',
                'assistance_recipients.assistance_program_id',
                'assistance_recipients.target_type',
                'assistance_recipients.family_id',
                'assistance_recipients.resident_id',
                'assistance_recipients.kepala_keluarga_id',
                'assistance_recipients.penerima_lapangan_id',
                'assistance_recipients.status',
                'assistance_recipients.tanggal_penyaluran',
                'assistance_recipients.catatan',
                'assistance_programs.nama_program',
                'assistance_programs.tahun',
                'assistance_programs.periode'
            )
            ->leftJoin('assistance_programs', 'assistance_recipients.assistance_program_id', '=', 'assistance_programs.id');

        // Filter berdasarkan program_id - wajib
        $programId = request('filter_program_id') ?? request('program_id');
        if ($programId) {
            $query->where('assistance_recipients.assistance_program_id', $programId);
        }

        // Filter berdasarkan target_type
        if (request('filter_target_type')) {
            $query->where('assistance_recipients.target_type', request('filter_target_type'));
        }

        // Filter berdasarkan status
        if (request('filter_status')) {
            $query->where('assistance_recipients.status', request('filter_status'));
        }

        // Filter berdasarkan RT/RW
        if (request('filter_rw_id') || request('filter_rt_id')) {
            $query->where(function ($q) {
                // Filter untuk KELUARGA
                $q->where(function ($q2) {
                    $q2->where('assistance_recipients.target_type', 'KELUARGA')
                        ->whereHas('family.house.rt.rw', function ($q3) {
                            if (request('filter_rw_id')) {
                                $q3->where('rws.id', request('filter_rw_id'));
                            }
                            if (request('filter_rt_id')) {
                                $q3->where('rts.id', request('filter_rt_id'));
                            }
                        });
                })
                // Filter untuk INDIVIDU
                ->orWhere(function ($q2) {
                    $q2->where('assistance_recipients.target_type', 'INDIVIDU')
                        ->whereHas('resident.family.house.rt.rw', function ($q3) {
                            if (request('filter_rw_id')) {
                                $q3->where('rws.id', request('filter_rw_id'));
                            }
                            if (request('filter_rt_id')) {
                                $q3->where('rts.id', request('filter_rt_id'));
                            }
                        });
                });
            });
        }

        // Cari data berdasarkan keyword
        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('assistance_programs.nama_program', 'like', '%' . $searchTerm . '%')
                    ->orWhere('assistance_programs.periode', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('family', function ($q2) use ($searchTerm) {
                        $q2->where('no_kk', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('resident', function ($q2) use ($searchTerm) {
                        $q2->where('nama', 'like', '%' . $searchTerm . '%')
                            ->orWhere('nik', 'like', '%' . $searchTerm . '%');
                    });
            });
        }

        // Sorting data
        if (request('sort')) {
            $order = request('order', 'asc');
            $sortMapping = [
                'nama_program' => 'assistance_programs.nama_program',
                'target_type'  => 'assistance_recipients.target_type',
                'status'       => 'assistance_recipients.status',
                'tanggal_penyaluran' => 'assistance_recipients.tanggal_penyaluran',
            ];

            $sortColumn = $sortMapping[request('sort')] ?? 'assistance_recipients.id';
            $query->orderBy($sortColumn, $order);
        } else {
            $query->orderBy('assistance_recipients.id', 'desc'); // Default sort by id desc
        }

        // Pagination
        $perPage = (int) request('per_page', 10);
        
        // Kalau per_page = -1, ambil semua data
        if ($perPage === -1) {
            $allRecipients = $query->get();
            $transformedRecipients = $allRecipients->map(function ($recipient) {
                return $this->transformRecipient($recipient);
            });

            $data += [
                'assistance_recipients' => $transformedRecipients,
                'meta' => [
                    'total'        => $transformedRecipients->count(),
                    'current_page' => 1,
                    'per_page'     => -1,
                    'search'       => request('search', ''),
                    'sort'         => request('sort', ''),
                    'order'        => request('order', 'asc'),
                ],
            ];
            return $data;
        }

        // Pagination normal
        $page           = (int) request('page', 0);
        $pageForLaravel = $page < 1 ? 1 : $page + 1;
        $recipients     = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);

        // Transform data untuk frontend
        $transformedRecipients = $recipients->getCollection()->map(function ($recipient) {
            return $this->transformRecipient($recipient);
        });

        $data += [
            'assistance_recipients' => $transformedRecipients,
            'meta' => [
                'total'        => $recipients->total(),
                'current_page' => $recipients->currentPage(),
                'per_page'     => $recipients->perPage(),
                'search'       => request('search', ''),
                'sort'         => request('sort', ''),
                'order'        => request('order', 'asc'),
            ],
        ];

        return $data;
    }

    /**
     * Get family residents untuk dropdown perwakilan
     */
    public function getFamilyResidents($recipient)
    {
        $familyId = null;
        
        if ($recipient->target_type === 'KELUARGA') {
            $familyId = $recipient->family_id;
        } else if ($recipient->target_type === 'INDIVIDU' && $recipient->resident_id) {
            $resident = Residents::with('family')->find($recipient->resident_id);
            if ($resident && $resident->family) {
                $familyId = $resident->family_id;
            }
        }

        if (!$familyId) {
            return [];
        }

        // Ambil semua residents dari family yang sama (termasuk dari house yang sama jika perlu)
        $family = Families::with('house.families.residents')->find($familyId);
        if (!$family || !$family->house) {
            return [];
        }

        $residents = [];
        // Ambil residents dari semua families di house yang sama
        foreach ($family->house->families as $fam) {
            foreach ($fam->residents as $resident) {
                // Skip jika sudah ada (untuk menghindari duplikasi)
                if (!collect($residents)->contains('id', $resident->id)) {
                    $residents[] = [
                        'id' => $resident->id,
                        'nik' => $resident->nik,
                        'nama' => $resident->nama,
                        'family_id' => $resident->family_id,
                    ];
                }
            }
        }

        return $residents;
    }

    /**
     * Update distribution status
     */
    public function updateDistribution($id, $data)
    {
        try {
            DB::beginTransaction();

            $recipient = $this->model::find($id);
            if (!$recipient) {
                throw new \Exception('Penerima tidak ditemukan');
            }

            // Validasi logika
            if ($data['status'] === 'DATANG') {
                // Jika DATANG, tanggal_penyaluran wajib
                if (empty($data['tanggal_penyaluran'])) {
                    throw new \Exception('Tanggal penyaluran wajib diisi untuk status DATANG');
                }
                // penerima_lapangan_id opsional
            } else if ($data['status'] === 'TIDAK_DATANG') {
                // Jika TIDAK_DATANG, penerima_lapangan_id harus NULL
                $data['penerima_lapangan_id'] = null;
                // tanggal_penyaluran boleh diisi atau tidak
            }

            // Update data
            $updateData = [
                'status' => $data['status'],
                'tanggal_penyaluran' => $data['tanggal_penyaluran'] ?? null,
                'penerima_lapangan_id' => $data['penerima_lapangan_id'] ?? null,
                'catatan' => $data['catatan'] ?? null,
            ];

            $updateData = $this->customDataCreateUpdate($updateData);
            
            $recipient->update($updateData);
            $recipient = $this->callbackAfterStoreOrUpdate($recipient, $updateData);

            // Check apakah semua penerima sudah tidak PROSES
            $programId = $recipient->assistance_program_id;
            $hasProses = $this->model::where('assistance_program_id', $programId)
                ->where('status', 'PROSES')
                ->whereNull('deleted_at')
                ->exists();

            if (!$hasProses) {
                // Update program status menjadi SELESAI melalui repository agar callback terpanggil
                $program = AssistanceProgram::find($programId);
                if ($program && $program->status !== 'SELESAI') {
                    $programRepository = app(AssistanceProgramsRepository::class);
                    $programRepository->update($programId, ['status' => 'SELESAI']);
                }
            }

            DB::commit();
            return $recipient;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}

