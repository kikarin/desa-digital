<?php

namespace App\Repositories;

use App\Models\AssistanceProgramItem;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\UniqueConstraintViolationException;
use Exception;

class AssistanceProgramItemsRepository
{
    use RepositoryTrait;

    protected $model;

    /**
     * Constructor - set model dan relasi yang akan di-load
     */
    public function __construct(AssistanceProgramItem $model)
    {
        $this->model = $model;
        // Load relasi program, item, dan user untuk tracking
        $this->with = ['program', 'item', 'created_by_user', 'updated_by_user'];
    }

    /**
     * Override create method untuk handle soft deleted records
     * Jika ada soft deleted record dengan kombinasi yang sama, force delete dulu
     */
    public function create($data)
    {
        try {
            DB::beginTransaction();
            
            // Check apakah ada record aktif (tidak soft deleted) dengan kombinasi yang sama
            $existing = $this->model::where('assistance_program_id', $data['assistance_program_id'])
                ->where('assistance_item_id', $data['assistance_item_id'])
                ->first();
            
            // Jika ada record aktif, throw error dengan message yang user-friendly
            if ($existing) {
                DB::rollback();
                throw new \Exception('Item ini sudah ditambahkan ke program ini.');
            }
            
            // Check apakah ada soft deleted record dengan kombinasi yang sama
            $softDeleted = $this->model::withTrashed()
                ->where('assistance_program_id', $data['assistance_program_id'])
                ->where('assistance_item_id', $data['assistance_item_id'])
                ->whereNotNull('deleted_at')
                ->first();
            
            // Jika ada soft deleted record, force delete dulu
            if ($softDeleted) {
                $softDeleted->forceDelete();
            }
            
            $data = $this->customDataCreateUpdate($data);
            $record = $this->model::create($data);
            $record = $this->callbackAfterStoreOrUpdate($record, $data);
            DB::commit();
            return $record;
        } catch (UniqueConstraintViolationException $e) {
            DB::rollback();
            // Convert database error ke message yang user-friendly
            throw new \Exception('Item ini sudah ditambahkan ke program ini.');
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Custom data untuk halaman index
     * Handle search, sort, dan pagination
     */
    public function customIndex($data)
    {
        $query = $this->model->with(['program', 'item'])
            ->select(
                'assistance_program_items.id',
                'assistance_program_items.assistance_program_id',
                'assistance_program_items.assistance_item_id',
                'assistance_program_items.jumlah',
                'assistance_programs.nama_program',
                'assistance_items.nama_item',
                'assistance_items.tipe',
                'assistance_items.satuan'
            )
            ->leftJoin('assistance_programs', 'assistance_program_items.assistance_program_id', '=', 'assistance_programs.id')
            ->leftJoin('assistance_items', 'assistance_program_items.assistance_item_id', '=', 'assistance_items.id');

        // Filter berdasarkan program_id - check dari filter_program_id atau program_id query parameter
        $programId = request('filter_program_id') ?? request('program_id');
        if ($programId) {
            $query->where('assistance_program_items.assistance_program_id', $programId);
        }

        // Filter berdasarkan item_id
        if (request('filter_item_id')) {
            $query->where('assistance_program_items.assistance_item_id', request('filter_item_id'));
        }

        // Cari data berdasarkan keyword
        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('assistance_programs.nama_program', 'like', '%' . $searchTerm . '%')
                    ->orWhere('assistance_items.nama_item', 'like', '%' . $searchTerm . '%')
                    ->orWhere('assistance_items.satuan', 'like', '%' . $searchTerm . '%');
            });
        }

        // Sorting data
        if (request('sort')) {
            $order = request('order', 'asc');
            $sortMapping = [
                'nama_program' => 'assistance_programs.nama_program',
                'nama_item'   => 'assistance_items.nama_item',
                'jumlah'      => 'assistance_program_items.jumlah',
                'satuan'      => 'assistance_items.satuan',
            ];

            $sortColumn = $sortMapping[request('sort')] ?? 'assistance_program_items.id';
            $query->orderBy($sortColumn, $order);
        } else {
            $query->orderBy('assistance_program_items.id', 'desc'); // Default sort by id desc
        }

        // Pagination
        $perPage = (int) request('per_page', 10);
        
        // Kalau per_page = -1, ambil semua data
        if ($perPage === -1) {
            $allItems = $query->get();
            $transformedItems = $allItems->map(function ($item) {
                return [
                    'id'                    => $item->id,
                    'assistance_program_id' => $item->assistance_program_id,
                    'assistance_item_id'    => $item->assistance_item_id,
                    'jumlah'                => $item->jumlah,
                    'satuan'                => $item->satuan,
                    'nama_program'          => $item->nama_program,
                    'nama_item'             => $item->nama_item,
                    'tipe'                  => $item->tipe,
                ];
            });

            $data += [
                'assistance_program_items' => $transformedItems,
                'meta' => [
                    'total'        => $transformedItems->count(),
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
        $items          = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);

        // Transform data untuk frontend
        $transformedItems = $items->getCollection()->map(function ($item) {
            return [
                'id'                    => $item->id,
                'assistance_program_id' => $item->assistance_program_id,
                'assistance_item_id'    => $item->assistance_item_id,
                'jumlah'                => $item->jumlah,
                'satuan'                => $item->satuan ?? ($item->item->satuan ?? ''),
                'nama_program'          => $item->nama_program,
                'nama_item'             => $item->nama_item,
                'tipe'                  => $item->tipe,
            ];
        });

        $data += [
            'assistance_program_items' => $transformedItems,
            'meta' => [
                'total'        => $items->total(),
                'current_page' => $items->currentPage(),
                'per_page'     => $items->perPage(),
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
        // Load list program dan item untuk dropdown
        $programRepository = app(\App\Repositories\AssistanceProgramsRepository::class);
        $itemRepository = app(\App\Repositories\AssistanceItemsRepository::class);
        
        $programs = $programRepository->getAll([], false, false);
        $items = $itemRepository->getAll([], false, false);
        
        $data['list_programs'] = $programs->map(function ($program) {
            return [
                'value' => $program->id,
                'label' => $program->nama_program . ' (' . $program->tahun . ')',
            ];
        })->toArray();
        
        $data['list_items'] = $items->map(function ($item) {
            return [
                'value' => $item->id,
                'label' => $item->nama_item . ' (' . $item->satuan . ')',
                'satuan' => $item->satuan, // Untuk auto-fill satuan
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
            // Load relasi program dan item
            $item->load(['program', 'item']);
            
            // Tambahkan nama program dan item ke data
            $data['item'] = array_merge($item->toArray(), [
                'nama_program' => $item->program ? $item->program->nama_program : null,
                'nama_item'    => $item->item ? $item->item->nama_item : null,
                'satuan'       => $item->item ? $item->item->satuan : null,
            ]);
        }
        
        return $data;
    }
}

