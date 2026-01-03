<?php

namespace App\Http\Controllers;

use App\Http\Requests\AduanMasyarakatRequest;
use App\Repositories\AduanMasyarakatRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        return response()->json([
            'data' => $data['aduan_masyarakat'] ?? [],
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
        return response()->json([
            'data' => $data['aduan_masyarakat'] ?? [],
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
     * Halaman verifikasi
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
        $data = $this->repository->customShow($data, $item);
        return inertia('modules/aduan-masyarakat/Verifikasi', $data);
    }

    /**
     * Store verifikasi (update status)
     */
    public function storeVerifikasi(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:menunggu_verifikasi,selesai,dibatalkan',
        ]);

        $item = $this->repository->getById($id);
        
        $updateData = [
            'status' => $request->status,
        ];
        
        // Jika ada catatan, tambahkan ke update data
        if ($request->has('catatan') && $request->catatan) {
            // Catatan bisa disimpan di field alasan_penolakan jika status dibatalkan
            // atau bisa dibuat field baru, untuk sementara kita simpan di alasan_melaporkan jika perlu
            // Tapi lebih baik buat field catatan_verifikasi atau gunakan alasan_penolakan untuk dibatalkan
            if ($request->status === 'dibatalkan') {
                $updateData['alasan_melaporkan'] = $request->catatan;
            }
        }
        
        $this->repository->update($id, $updateData);

        return redirect()->route('aduan-masyarakat.show', $id)->with('success', 'Status aduan berhasil diperbarui');
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
        
        $model = $this->repository->create($data);
        
        if (!($model instanceof \Illuminate\Database\Eloquent\Model)) {
            return $model;
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
        
        $model = $this->repository->update($id, $data);
        
        if (!($model instanceof \Illuminate\Database\Eloquent\Model)) {
            return $model;
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

