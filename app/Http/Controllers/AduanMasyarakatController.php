<?php

namespace App\Http\Controllers;

use App\Http\Requests\AduanMasyarakatRequest;
use App\Http\Requests\VerifikasiRtAduanMasyarakatRequest;
use App\Repositories\AduanMasyarakatRepository;
use App\Traits\BaseTrait;
use App\Models\UsersRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AduanMasyarakatController extends Controller implements HasMiddleware
{
    use BaseTrait;
    
    private $repository;
    private $request;

    public function __construct(AduanMasyarakatRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = AduanMasyarakatRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'aduan-masyarakat';
        $this->commonData['kode_first_menu']  = 'ADUAN-MASYARAKAT';
        $this->commonData['kode_second_menu'] = $this->kode_menu;
    }

    public static function middleware(): array
    {
        $className  = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));
        return [
            // Aduan Masyarakat (Admin) - hanya untuk melihat dan verifikasi
            new Middleware("can:$permission Show", only: ['index', 'show']),
            new Middleware("can:$permission Detail", only: ['show']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
            new Middleware("can:$permission Verifikasi", only: ['verifikasi', 'storeVerifikasi']),
            // Aduan Masyarakat tidak perlu Add dan Edit permission
            // Separate permission for Aduan Saya (warga) - CRUD biasa
            new Middleware("can:Aduan Saya Show", only: ['indexSaya', 'showSaya', 'apiIndexSaya']),
            new Middleware("can:Aduan Saya Add", only: ['createSaya', 'store']),
            new Middleware("can:Aduan Saya Edit", only: ['editSaya', 'update']),
            new Middleware("can:Aduan Saya Delete", only: ['destroy']),
        ];
    }

    public function apiIndex()
    {
        $data = $this->repository->customIndex([], false);
        
        // Convert Collection to array if needed
        $aduanMasyarakat = $data['aduan_masyarakat'] ?? [];
        if (is_object($aduanMasyarakat) && method_exists($aduanMasyarakat, 'toArray')) {
            $aduanMasyarakat = $aduanMasyarakat->toArray();
        }
        
        return response()->json([
            'data' => $aduanMasyarakat,
            'meta' => [
                'total'        => $data['meta']['total'] ?? 0,
                'current_page' => $data['meta']['current_page'] ?? 1,
                'per_page'     => $data['meta']['per_page'] ?? 10,
                'search'       => $data['meta']['search'] ?? '',
                'sort'         => $data['meta']['sort'] ?? '',
                'order'        => $data['meta']['order'] ?? 'desc',
            ],
        ]);
    }

    /**
     * API Index untuk aduan-saya (warga) - hanya menampilkan aduan mereka sendiri
     */
    public function apiIndexSaya()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'total'        => 0,
                    'current_page' => 1,
                    'per_page'     => 10,
                    'search'       => request('search', ''),
                    'sort'         => request('sort', ''),
                    'order'        => request('order', 'desc'),
                ],
            ], 401);
        }
        
        $data = $this->repository->customIndex([], true);
        
        // Convert Collection to array if needed
        $aduanMasyarakat = $data['aduan_masyarakat'] ?? [];
        if (is_object($aduanMasyarakat) && method_exists($aduanMasyarakat, 'toArray')) {
            $aduanMasyarakat = $aduanMasyarakat->toArray();
        }
        
        return response()->json([
            'data' => $aduanMasyarakat,
            'meta' => [
                'total'        => $data['meta']['total'] ?? 0,
                'current_page' => $data['meta']['current_page'] ?? 1,
                'per_page'     => $data['meta']['per_page'] ?? 10,
                'search'       => $data['meta']['search'] ?? '',
                'sort'         => $data['meta']['sort'] ?? '',
                'order'        => $data['meta']['order'] ?? 'desc',
            ],
        ]);
    }

    public function apiShow($id)
    {
        $item = $this->repository->getById($id);
        if (!$item) {
            return response()->json(['error' => 'Aduan Masyarakat not found'], 404);
        }

        $data = $this->commonData + ['item' => $item];
        $data = $this->repository->customShow($data, $item);
        
        return response()->json($data);
    }

    /**
     * API Index untuk verifikasi RT - hanya menampilkan aduan dari warga RT mereka
     */
    public function apiIndexRt()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'data' => [],
                    'meta' => [
                        'total'        => 0,
                        'current_page' => 1,
                        'per_page'     => 10,
                        'search'       => request('search', ''),
                        'sort'         => request('sort', ''),
                        'order'        => request('order', 'desc'),
                    ],
                ], 401);
            }

            $userRole = UsersRole::where('users_id', $user->id)
                ->where('role_id', 36) 
                ->whereNotNull('rt_id')
                ->first();
            
            if (!$userRole || !$userRole->rt_id) {
                return response()->json([
                    'data' => [],
                    'meta' => [
                        'total'        => 0,
                        'current_page' => 1,
                        'per_page'     => 10,
                        'search'       => request('search', ''),
                        'sort'         => request('sort', ''),
                        'order'        => request('order', 'desc'),
                    ],
                ], 403);
            }

            // Filter aduan dari warga di RT yang sama (melalui created_by -> users -> residents -> families -> houses -> rt_id)
            $query = $this->repository->getModel()
                ->with(['kategori_aduan', 'kecamatan', 'desa', 'created_by_user', 'updated_by_user', 'files', 'layanan_darurat', 'rt_verifikasi', 'admin_verifikasi'])
                ->whereHas('created_by_user', function ($q) use ($userRole) {
                    $q->whereHas('resident', function ($rq) use ($userRole) {
                        $rq->whereHas('family', function ($fq) use ($userRole) {
                            $fq->whereHas('house', function ($hq) use ($userRole) {
                                $hq->where('rt_id', $userRole->rt_id);
                            });
                        });
                    });
                });

            // Search
            if (request('search')) {
                $searchTerm = request('search');
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('judul', 'like', '%' . $searchTerm . '%')
                        ->orWhere('detail_aduan', 'like', '%' . $searchTerm . '%');
                });
            }

            // Filter by status
            if (request('status')) {
                $query->where('status', request('status'));
            }

            // Sorting
            if (request('sort')) {
                $order = request('order', 'asc');
                $sortMapping = [
                    'judul' => 'judul',
                    'status' => 'status',
                    'created_at' => 'created_at',
                ];
                $sortColumn = $sortMapping[request('sort')] ?? 'id';
                $query->orderBy($sortColumn, $order);
            } else {
                $query->orderBy('id', 'desc');
            }

            // Pagination
            $perPage = (int) request('per_page', 10);
            $page = (int) request('page', 0);
            $pageForLaravel = $page < 1 ? 1 : $page + 1;
            
            if ($perPage === -1) {
                $items = $query->get();
            } else {
                $items = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);
            }

            // Transform data
            $transformedData = ($perPage === -1 ? $items : $items->getCollection())->map(function ($item) {
                $data = $this->repository->customShow([], $item);
                return $data['item'] ?? $item;
            });

            return response()->json([
                'data' => $transformedData,
                'meta' => $perPage === -1 ? [
                    'total' => $transformedData->count(),
                    'current_page' => 1,
                    'per_page' => -1,
                    'search' => request('search', ''),
                    'sort' => request('sort', ''),
                    'order' => request('order', 'asc'),
                ] : [
                    'total' => $items->total(),
                    'current_page' => $items->currentPage(),
                    'per_page' => $items->perPage(),
                    'search' => request('search', ''),
                    'sort' => request('sort', ''),
                    'order' => request('order', 'asc'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in apiIndexRt: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            
            return response()->json([
                'data' => [],
                'meta' => [
                    'total'        => 0,
                    'current_page' => 1,
                    'per_page'     => 10,
                    'search'       => request('search', ''),
                    'sort'         => request('sort', ''),
                    'order'        => request('order', 'desc'),
                ],
                'error' => config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan saat mengambil data',
            ], 500);
        }
    }

    /**
     * Halaman index untuk admin
     */
    public function index()
    {
        $data = $this->commonData + [];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customIndex($data, false);
        return inertia('modules/aduan-masyarakat/Index', $data);
    }

    /**
     * Halaman index untuk warga (Aduan Saya)
     */
    public function indexSaya()
    {
        $data = $this->commonData + [];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customIndex($data, true);
        return inertia('modules/aduan-saya/Index', $data);
    }

    /**
     * Index untuk RT - menampilkan aduan dari warga RT mereka
     */
    public function indexRt()
    {
        $user = Auth::user();
        
        $userRole = UsersRole::where('users_id', $user->id)
            ->where('role_id', 36) 
            ->whereNotNull('rt_id')
            ->first();
        
        if (!$userRole || !$userRole->rt_id) {
            abort(403, 'Anda tidak memiliki akses untuk halaman ini.');
        }

        $data = $this->commonData + [];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        
        // Set permission khusus untuk RT (override jika ada)
        $data['can'] = array_merge($data['can'] ?? [], [
            'Verifikasi' => $user && method_exists($user, 'can') ? $user->can('Aduan Masyarakat RT Verifikasi') : true, // Default true untuk RT
        ]);
        
        // Filter aduan dari warga di RT yang sama (melalui created_by -> users -> residents -> families -> houses -> rt_id)
        $query = $this->repository->getModel()
            ->with(['kategori_aduan', 'kecamatan', 'desa', 'created_by_user', 'files', 'layanan_darurat'])
            ->whereHas('created_by_user', function ($q) use ($userRole) {
                $q->whereHas('resident', function ($rq) use ($userRole) {
                    $rq->whereHas('family', function ($fq) use ($userRole) {
                        $fq->whereHas('house', function ($hq) use ($userRole) {
                            $hq->where('rt_id', $userRole->rt_id);
                        });
                    });
                });
            });

        // Search
        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('judul', 'like', '%' . $searchTerm . '%')
                    ->orWhere('detail_aduan', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter by status
        if (request('status')) {
            $query->where('status', request('status'));
        }

        // Sorting
        if (request('sort')) {
            $order = request('order', 'asc');
            $sortMapping = [
                'judul' => 'judul',
                'status' => 'status',
            ];
            $sortColumn = $sortMapping[request('sort')] ?? 'id';
            $query->orderBy($sortColumn, $order);
        } else {
            $query->orderBy('id', 'desc');
        }

        // Pagination
        $perPage = (int) request('per_page', 10);
        $page = (int) request('page', 0);
        $pageForLaravel = $page < 1 ? 1 : $page + 1;
        
        if ($perPage === -1) {
            $items = $query->get();
        } else {
            $items = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);
        }

        // Transform data
        $transformedData = ($perPage === -1 ? $items : $items->getCollection())->map(function ($item) {
            $data = $this->repository->customShow([], $item);
            return $data['item'] ?? $item;
        });

        $data['aduan_masyarakat'] = $transformedData;
        $data['meta'] = $perPage === -1 ? [
            'total' => $transformedData->count(),
            'current_page' => 1,
            'per_page' => -1,
            'search' => request('search', ''),
            'sort' => request('sort', ''),
            'order' => request('order', 'asc'),
        ] : [
            'total' => $items->total(),
            'current_page' => $items->currentPage(),
            'per_page' => $items->perPage(),
            'search' => request('search', ''),
            'sort' => request('sort', ''),
            'order' => request('order', 'asc'),
        ];
        
        return inertia('modules/aduan-masyarakat-rt/Index', $data);
    }

    /**
     * Show untuk RT
     */
    public function showRt($id)
    {
        $user = Auth::user();
        
        $userRole = UsersRole::where('users_id', $user->id)
            ->where('role_id', 36) 
            ->whereNotNull('rt_id')
            ->first();
        
        if (!$userRole || !$userRole->rt_id) {
            abort(403, 'Anda tidak memiliki akses untuk halaman ini.');
        }

        $item = $this->repository->getById($id);
        
        // Cek apakah aduan dibuat oleh warga di RT yang sama
        $createdByUser = $item->created_by_user;
        if (!$createdByUser || !$createdByUser->resident_id) {
            abort(403, 'Aduan ini tidak memiliki data warga yang valid.');
        }
        
        $resident = \App\Models\Residents::find($createdByUser->resident_id);
        if (!$resident || !$resident->family || !$resident->family->house || $resident->family->house->rt_id != $userRole->rt_id) {
            abort(403, 'Aduan ini bukan dari warga di RT Anda.');
        }
        
        $data = $this->commonData + [
            'item' => $item,
        ];
        
        $data['can'] = [
            'Verifikasi' => $user && method_exists($user, 'can') ? $user->can('Aduan Masyarakat RT Verifikasi') : false,
        ];
        
        $data = $this->repository->customShow($data, $item);
        return inertia('modules/aduan-masyarakat-rt/Show', $data);
    }

    /**
     * Halaman verifikasi (Admin Desa)
     */
    public function verifikasi($id)
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getById($id);
        
        $data = $this->commonData + [
            'item' => $item,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        if ($item->status !== 'diverifikasi_rt') {
            return redirect()->route('aduan-masyarakat.show', $id)
                ->with('error', 'Aduan harus diverifikasi RT terlebih dahulu.');
        }

        $data = $this->repository->customShow($data, $item);
        return inertia('modules/aduan-masyarakat/Verifikasi', $data);
    }

    /**
     * Show verifikasi form untuk RT
     */
    public function verifikasiRt($id)
    {
        $user = Auth::user();
        
        $userRole = UsersRole::where('users_id', $user->id)
            ->where('role_id', 36) 
            ->whereNotNull('rt_id')
            ->first();
        
        if (!$userRole || !$userRole->rt_id) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk verifikasi RT.');
        }

        $aduan = $this->repository->getById($id);
        
        // Cek apakah aduan dibuat oleh warga di RT yang sama
        $createdByUser = $aduan->created_by_user;
        if (!$createdByUser || !$createdByUser->resident_id) {
            return redirect()->back()->with('error', 'Aduan ini tidak memiliki data warga yang valid.');
        }
        
        $resident = \App\Models\Residents::find($createdByUser->resident_id);
        if (!$resident || !$resident->family || !$resident->family->house || $resident->family->house->rt_id != $userRole->rt_id) {
            return redirect()->back()->with('error', 'Aduan ini bukan dari warga di RT Anda.');
        }
        
        if ($aduan->status !== 'menunggu_verifikasi') {
            return redirect()->back()->with('error', 'Aduan ini sudah diverifikasi atau tidak dapat diverifikasi.');
        }

        $data = $this->commonData + [
            'item' => $aduan,
        ];

        $data = $this->repository->customShow($data, $aduan);
        return inertia("modules/aduan-masyarakat-rt/VerifikasiRt", $data);
    }

    /**
     * Store verifikasi RT
     */
    public function storeVerifikasiRt(VerifikasiRtAduanMasyarakatRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            
            $userRole = UsersRole::where('users_id', $user->id)
                ->where('role_id', 36) 
                ->whereNotNull('rt_id')
                ->first();
            
            if (!$userRole || !$userRole->rt_id) {
                return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk verifikasi RT.');
            }

            $aduan = $this->repository->getById($request->id);
            
            // Cek apakah aduan dibuat oleh warga di RT yang sama
            $createdByUser = $aduan->created_by_user;
            if (!$createdByUser || !$createdByUser->resident_id) {
                return redirect()->back()->with('error', 'Aduan ini tidak memiliki data warga yang valid.');
            }
            
            $resident = \App\Models\Residents::find($createdByUser->resident_id);
            if (!$resident || !$resident->family || !$resident->family->house || $resident->family->house->rt_id != $userRole->rt_id) {
                return redirect()->back()->with('error', 'Aduan ini bukan dari warga di RT Anda.');
            }
            
            if ($aduan->status !== 'menunggu_verifikasi') {
                return redirect()->back()->with('error', 'Aduan ini sudah diverifikasi atau tidak dapat diverifikasi.');
            }

            $data = [
                'status' => $request->status,
                'rt_verifikasi_id' => $user->id,
                'rt_verifikasi_at' => now(),
                'rt_catatan' => $request->rt_catatan,
            ];

            if ($request->status === 'dibatalkan') {
                $data['alasan_melaporkan'] = $request->rt_catatan;
            }

            // Update menggunakan repository untuk memastikan callback terpanggil
            $this->repository->update($aduan->id, $data);

            DB::commit();
            return redirect()->route('aduan-masyarakat-rt.index')
                ->with('success', 'Aduan berhasil diverifikasi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memverifikasi aduan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Store verifikasi (Admin Desa) - harus setelah verifikasi RT
     */
    public function storeVerifikasi(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'status' => 'required|in:diverifikasi_admin,selesai,dibatalkan',
                'admin_catatan' => 'nullable|string|max:1000',
            ]);

            $item = $this->repository->getById($id);
            
            if ($item->status !== 'diverifikasi_rt') {
                return redirect()->back()->with('error', 'Aduan harus diverifikasi RT terlebih dahulu.');
            }
            
            $updateData = [
                'status' => $request->status,
                'admin_verifikasi_id' => Auth::id(),
                'admin_verifikasi_at' => now(),
                'admin_catatan' => $request->admin_catatan,
            ];
            
            if ($request->status === 'dibatalkan') {
                $updateData['alasan_melaporkan'] = $request->admin_catatan;
            }
            
            $this->repository->update($id, $updateData);

            DB::commit();
            return redirect()->route('aduan-masyarakat.show', $id)->with('success', 'Status aduan berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui status aduan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Get list kategori aduan
     */
    public function getKategoriAduan()
    {
        $kategoris = \App\Models\KategoriAduan::select('id', 'nama')
            ->orderBy('nama', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->nama,
                ];
            });

        return response()->json($kategoris);
    }

    /**
     * Override store untuk handle multiple files
     */
    public function store(Request $request)
    {
        $this->request = $request;
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->request->validate($this->getValidationRules());
        $data = $this->request->all();
        
        // Pastikan jenis_aduan selalu ada dan valid
        if (!isset($data['jenis_aduan']) || !in_array($data['jenis_aduan'], ['publik', 'private'])) {
            $data['jenis_aduan'] = $request->input('jenis_aduan', 'publik');
        }
        
        // Extract files
        $files = [];
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            if (!is_array($files)) {
                $files = [$files];
            }
        }
        unset($data['files']);
        
        $before = $this->repository->callbackBeforeStoreOrUpdate($data, 'store');
        if ($before['error'] != 0) {
            return redirect()->back()->with('error', $before['message'])->withInput();
        } else {
            $data = $before['data'];
        }
        
        // Pastikan jenis_aduan masih ada setelah callback
        if (!isset($data['jenis_aduan']) || !in_array($data['jenis_aduan'], ['publik', 'private'])) {
            $data['jenis_aduan'] = $request->input('jenis_aduan', 'publik');
        }
        
        $data = $this->repository->customDataCreateUpdate($data);
        
        // Pastikan jenis_aduan masih ada setelah customDataCreateUpdate
        if (!isset($data['jenis_aduan']) || !in_array($data['jenis_aduan'], ['publik', 'private'])) {
            $data['jenis_aduan'] = $request->input('jenis_aduan', 'publik');
        }
        
        // Handle layanan_darurat_ids
        $layananDaruratIds = $request->input('layanan_darurat_ids', []);
        unset($data['layanan_darurat_ids']);
        
        $model = $this->repository->create($data);
        
        if (!($model instanceof \Illuminate\Database\Eloquent\Model)) {
            return $model;
        }
        
        // Attach layanan darurat
        if (!empty($layananDaruratIds) && is_array($layananDaruratIds)) {
            $model->layanan_darurat()->sync($layananDaruratIds);
        }
        
        // Store files after record is created
        if (!empty($files)) {
            $this->repository->storeFiles($model, $files);
        }
        
        return redirect()->route($this->route . '.index')->with('success', trans('message.success_add'));
    }

    /**
     * Override update untuk handle multiple files
     */
    public function update(Request $request, $id)
    {
        $this->request = $request;
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $data = $this->request->validate($this->getValidationRules());
        $data = $this->request->all();
        
        // Pastikan jenis_aduan selalu ada dan valid
        if (!isset($data['jenis_aduan']) || !in_array($data['jenis_aduan'], ['publik', 'private'])) {
            $data['jenis_aduan'] = $request->input('jenis_aduan', 'publik');
        }
        
        // Extract files
        $files = [];
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            if (!is_array($files)) {
                $files = [$files];
            }
        }
        unset($data['files']);
        
        // Handle deleted files
        if ($request->has('deleted_files')) {
            $data['deleted_files'] = $request->input('deleted_files', []);
        }
        
        $before = $this->repository->callbackBeforeStoreOrUpdate($data, 'update');
        if ($before['error'] != 0) {
            return redirect()->back()->with('error', $before['message'])->withInput();
        } else {
            $data = $before['data'];
        }
        
        // Pastikan jenis_aduan masih ada setelah callback
        if (!isset($data['jenis_aduan']) || !in_array($data['jenis_aduan'], ['publik', 'private'])) {
            $data['jenis_aduan'] = $request->input('jenis_aduan', 'publik');
        }
        
        $record = $this->repository->getById($id);
        $data = $this->repository->customDataCreateUpdate($data, $record);
        
        // Pastikan jenis_aduan masih ada setelah customDataCreateUpdate
        if (!isset($data['jenis_aduan']) || !in_array($data['jenis_aduan'], ['publik', 'private'])) {
            $data['jenis_aduan'] = $request->input('jenis_aduan', 'publik');
        }
        
        // Handle layanan_darurat_ids
        $layananDaruratIds = $request->input('layanan_darurat_ids', []);
        unset($data['layanan_darurat_ids']);
        
        $model = $this->repository->update($id, $data);
        
        if (!($model instanceof \Illuminate\Database\Eloquent\Model)) {
            return $model;
        }
        
        // Sync layanan darurat
        if (isset($layananDaruratIds) && is_array($layananDaruratIds)) {
            $model->layanan_darurat()->sync($layananDaruratIds);
        }
        
        // Store new files after record is updated
        if (!empty($files)) {
            $this->repository->storeFiles($model, $files);
        }
        
        return redirect()->route($this->route . '.show', $id)->with('success', trans('message.success_update'));
    }

    /**
     * Create untuk aduan-saya (warga)
     */
    public function createSaya()
    {
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + [
            'item' => null,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data);
        if (!is_array($data)) {
            return $data;
        }
        
        return inertia("modules/aduan-masyarakat/Create", $data);
    }

    /**
     * Show untuk aduan-saya (warga)
     */
    public function showSaya($id)
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getById($id);
        
        // Check if user can access this aduan (only their own - check created_by)
        if ($item->created_by !== Auth::id()) {
            return redirect()->route('aduan-saya.index')->with('error', 'Anda tidak memiliki akses ke aduan ini');
        }
        
        $data = $this->commonData + [
            'item' => $item,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customShow($data, $item);
        return inertia("modules/aduan-saya/Show", $data);
    }

    /**
     * Edit untuk aduan-saya (warga)
     */
    public function editSaya($id)
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getById($id);
        
        // Check if user can access this aduan (only their own - check created_by)
        if ($item->created_by !== Auth::id()) {
            return redirect()->route('aduan-saya.index')->with('error', 'Anda tidak memiliki akses ke aduan ini');
        }
        
        // Check if status allows editing (hanya menunggu_verifikasi atau dibatalkan)
        if ($item->status === 'selesai') {
            return redirect()->route('aduan-saya.show', $id)->with('error', 'Aduan yang sudah selesai tidak dapat diedit');
        }
        
        $data = $this->commonData + [
            'item' => $item,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data, $item);
        if (!is_array($data)) {
            return $data;
        }
        return inertia("modules/aduan-masyarakat/Edit", $data);
    }

    /**
     * Override getPermission to include Verifikasi permission
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
                'Verifikasi' => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($permission_main . ' Verifikasi') : false,
            ],
        ];
    }
}

