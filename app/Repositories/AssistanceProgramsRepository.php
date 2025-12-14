<?php

namespace App\Repositories;

use App\Models\AssistanceProgram;
use App\Models\AssistanceRecipient;
use App\Traits\RepositoryTrait;

class AssistanceProgramsRepository
{
    use RepositoryTrait;

    protected $model;

    /**
     * Constructor - set model dan relasi yang akan di-load
     */
    public function __construct(AssistanceProgram $model)
    {
        $this->model = $model;
        // Load relasi user untuk tracking
        $this->with = ['created_by_user', 'updated_by_user'];
    }

    /**
     * Custom data untuk halaman index
     * Handle search, sort, dan pagination
     */
    public function customIndex($data)
    {
        $query = $this->model->select(
            'assistance_programs.id', 
            'assistance_programs.nama_program', 
            'assistance_programs.tahun', 
            'assistance_programs.periode', 
            'assistance_programs.target_penerima', 
            'assistance_programs.status', 
            'assistance_programs.keterangan'
        )
        ->leftJoin('assistance_program_items', function($join) {
            $join->on('assistance_programs.id', '=', 'assistance_program_items.assistance_program_id')
                 ->whereNull('assistance_program_items.deleted_at'); // Abaikan soft deleted records
        })
        ->leftJoin('assistance_recipients', function($join) {
            $join->on('assistance_programs.id', '=', 'assistance_recipients.assistance_program_id')
                 ->whereNull('assistance_recipients.deleted_at'); // Abaikan soft deleted records
        })
        ->selectRaw('COUNT(DISTINCT assistance_program_items.id) as items_count')
        ->selectRaw('COUNT(DISTINCT assistance_recipients.id) as recipients_count')
        ->groupBy(
            'assistance_programs.id',
            'assistance_programs.nama_program',
            'assistance_programs.tahun',
            'assistance_programs.periode',
            'assistance_programs.target_penerima',
            'assistance_programs.status',
            'assistance_programs.keterangan'
        );

        // Filter berdasarkan status
        if (request('filter_status')) {
            $query->where('status', request('filter_status'));
        }

        // Filter berdasarkan target_penerima
        if (request('filter_target_penerima')) {
            $query->where('target_penerima', request('filter_target_penerima'));
        }

        // Filter berdasarkan tahun
        if (request('filter_tahun')) {
            $query->where('tahun', request('filter_tahun'));
        }

        // Cari data berdasarkan keyword
        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nama_program', 'like', '%' . $searchTerm . '%')
                    ->orWhere('periode', 'like', '%' . $searchTerm . '%')
                    ->orWhere('keterangan', 'like', '%' . $searchTerm . '%');
            });
        }

        // Sorting data
        if (request('sort')) {
            $order = request('order', 'asc');
            $sortMapping = [
                'nama_program'      => 'nama_program',
                'tahun'             => 'tahun',
                'periode'           => 'periode',
                'target_penerima'   => 'target_penerima',
                'status'            => 'status',
            ];

            $sortColumn = $sortMapping[request('sort')] ?? 'id';
            $query->orderBy($sortColumn, $order);
        } else {
            $query->orderBy('id', 'desc'); // Default sort by id desc
        }

        // Pagination
        $perPage = (int) request('per_page', 10);
        
        // Kalau per_page = -1, ambil semua data
        if ($perPage === -1) {
            $allPrograms = $query->get();
            $transformedPrograms = $allPrograms->map(function ($program) {
                return [
                    'id'             => $program->id,
                    'nama_program'  => $program->nama_program,
                    'tahun'         => $program->tahun,
                    'periode'       => $program->periode,
                    'target_penerima' => $program->target_penerima,
                    'status'        => $program->status,
                    'keterangan'    => $program->keterangan,
                    'items_count'   => (int) $program->items_count,
                    'recipients_count' => (int) $program->recipients_count,
                ];
            });

            $data += [
                'assistance_programs' => $transformedPrograms,
                'meta' => [
                    'total'        => $transformedPrograms->count(),
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
        $programs       = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);

        // Transform data untuk frontend
        $transformedPrograms = $programs->getCollection()->map(function ($program) {
            return [
                'id'             => $program->id,
                'nama_program'  => $program->nama_program,
                'tahun'         => $program->tahun,
                'periode'       => $program->periode,
                'target_penerima' => $program->target_penerima,
                'status'        => $program->status,
                'keterangan'    => $program->keterangan,
                'items_count'   => (int) $program->items_count,
                'recipients_count' => (int) $program->recipients_count,
            ];
        });

        $data += [
            'assistance_programs' => $transformedPrograms,
            'meta' => [
                'total'        => $programs->total(),
                'current_page' => $programs->currentPage(),
                'per_page'     => $programs->perPage(),
                'search'       => request('search', ''),
                'sort'         => request('sort', ''),
                'order'        => request('order', 'asc'),
            ],
        ];

        return $data;
    }

    /**
     * Custom data untuk halaman create/edit
     */
    public function customCreateEdit($data, $item = null)
    {
        return $data;
    }

    /**
     * Custom data untuk halaman show/detail
     */
    public function customShow($data, $item = null)
    {
        return $data;
    }

    /**
     * Callback setelah store atau update
     * Auto-update status penerima menjadi DATANG jika program status menjadi SELESAI
     */
    public function callbackAfterStoreOrUpdate($model, $data, $method = 'store', $record_sebelumnya = null)
    {
        // Refresh model untuk mendapatkan status terbaru
        $model->refresh();
        
        // Cek status sebelumnya
        $statusSebelumnya = $record_sebelumnya ? $record_sebelumnya->status : null;
        
        // Jika status program adalah SELESAI dan sebelumnya bukan SELESAI
        // Atau jika create baru dengan status SELESAI
        if ($model->status === 'SELESAI' && ($statusSebelumnya !== 'SELESAI' || $method === 'store')) {
            // Update semua penerima yang masih PROSES menjadi DATANG
            // Karena program selesai berarti semua penerima sudah datang
            AssistanceRecipient::where('assistance_program_id', $model->id)
                ->where('status', 'PROSES')
                ->update([
                    'status' => 'DATANG',
                    'tanggal_penyaluran' => now(),
                    'updated_by' => auth()->id(),
                ]);
        }

        return $model;
    }
}

