<?php

namespace App\Http\Controllers;

use App\Http\Requests\PengajuanSuratRequest;
use App\Http\Requests\VerifikasiPengajuanSuratRequest;
use App\Repositories\PengajuanSuratRepository;
use App\Repositories\AdminTandaTanganRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Models\PengajuanSuratAtribut;
use Barryvdh\DomPDF\Facade\Pdf;

class PengajuanSuratController extends Controller implements HasMiddleware
{
    use BaseTrait;
    
    private $repository;
    private $request;
    private $adminTandaTanganRepository;

    public function __construct(PengajuanSuratRepository $repository, Request $request, AdminTandaTanganRepository $adminTandaTanganRepository)
    {
        $this->repository = $repository;
        $this->request    = PengajuanSuratRequest::createFromBase($request);
        $this->adminTandaTanganRepository = $adminTandaTanganRepository;
        $this->initialize();
        $this->route                          = 'layanan-surat/pengajuan-surat';
        $this->commonData['kode_first_menu']  = 'LAYANAN-SURAT';
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
            new Middleware("can:$permission Verifikasi", only: ['verifikasi', 'storeVerifikasi']),
            new Middleware("can:$permission Export PDF", only: ['exportPdf']),
            new Middleware("can:$permission Preview PDF", only: ['previewPdf']),
            // Separate permission for Pengajuan Saya (warga)
            new Middleware("can:Pengajuan Saya Show", only: ['indexPengajuanSaya', 'createPengajuanSaya', 'showPengajuanSaya', 'editPengajuanSaya', 'apiIndexPengajuanSaya']),
            new Middleware("can:Pengajuan Saya Add", only: ['store']),
            new Middleware("can:Pengajuan Saya Edit", only: ['update']),
        ];
    }

    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);
        return response()->json([
            'data' => $data['pengajuan_surat'] ?? [],
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
     * API Index untuk pengajuan-saya (warga) - hanya menampilkan pengajuan mereka sendiri
     */
    public function apiIndexPengajuanSaya()
    {
        // Filter by created_by untuk warga (hanya menampilkan pengajuan yang dibuat oleh user yang login)
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
        
        // Set filter_created_by di request dan pass ke customIndex
        request()->merge(['filter_created_by' => $user->id]);
        
        // Panggil customIndex dengan filter yang sudah di-set
        $data = $this->repository->customIndex(['filter_created_by' => $user->id]);
        return response()->json([
            'data' => $data['pengajuan_surat'] ?? [],
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
     * Create untuk pengajuan-saya (warga)
     */
    public function createPengajuanSaya()
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
        
        // For warga, set resident_id automatically from logged in user
        $user = Auth::user();
        if ($user && $user->resident_id) {
            $data['resident_id'] = $user->resident_id;
        }
        
        // Override route untuk render view yang benar
        return inertia("modules/layanan-surat/pengajuan-saya/Create", $data);
    }

    /**
     * Show untuk pengajuan-saya (warga)
     */
    public function showPengajuanSaya($id)
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getById($id);
        
        // Check if user can access this pengajuan (only their own - check created_by)
        $user = Auth::user();
        if ($user && $item->created_by != $user->id) {
            abort(403, 'Anda tidak memiliki akses untuk melihat pengajuan ini.');
        }
        
        $data = $this->commonData + [
            'item' => $item,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customShow($data, $item);
        return inertia("modules/layanan-surat/pengajuan-saya/Show", $data);
    }

    /**
     * Edit untuk pengajuan-saya (warga)
     */
    public function editPengajuanSaya($id)
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getById($id);
        
        // Check if user can access this pengajuan (only their own - check created_by)
        $user = Auth::user();
        if ($user && $item->created_by != $user->id) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit pengajuan ini.');
        }
        
        // Check if pengajuan can be edited
        if (!$item->canBeEdited()) {
            return redirect()->route('pengajuan-saya.show', $id)
                ->with('error', 'Pengajuan surat tidak dapat diedit karena sudah disetujui.');
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
        return inertia("modules/layanan-surat/pengajuan-saya/Edit", $data);
    }

    /**
     * Index untuk pengajuan-saya (warga)
     */
    public function indexPengajuanSaya()
    {
        // Filter by created_by untuk warga (hanya menampilkan pengajuan yang dibuat oleh user yang login)
        $user = Auth::user();
        if ($user) {
            request()->merge(['filter_created_by' => $user->id]);
        }
        
        $data = $this->commonData + [];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customIndex($data);
        return inertia('modules/layanan-surat/pengajuan-saya/Index', $data);
    }

    /**
     * Override getPermission to include custom permissions
     */
    private function getPermission()
    {
        $auth_user = Auth::user();
        $permission_main = $this->permission_main;
        
        return [
            'can' => [
                'Add'    => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($permission_main . ' Add') : false,
                'Edit'   => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($permission_main . ' Edit') : false,
                'Delete' => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($permission_main . ' Delete') : false,
                'Detail' => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($permission_main . ' Detail') : false,
                'Verifikasi' => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($permission_main . ' Verifikasi') : false,
                'Export PDF' => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($permission_main . ' Export PDF') : false,
                'Preview PDF' => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($permission_main . ' Preview PDF') : false,
            ],
        ];
    }

    /**
     * Store pengajuan surat dengan atribut
     */
    public function store(Request $request)
    {
        $this->request = $request;
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->request->validate($this->getValidationRules());
        $data = $this->request->all();

        // Check if this is from pengajuan-saya route (warga)
        $user = Auth::user();
        $isPengajuanSaya = $request->routeIs('pengajuan-saya.*');
        
        // Permission sudah diatur melalui category permission, tidak perlu validasi tambahan
        // Jika dari pengajuan-saya, tetap bisa create tanpa harus punya resident_id

        $before = $this->repository->callbackBeforeStoreOrUpdate($data, 'store');
        if ($before['error'] != 0) {
            return redirect()->back()->with('error', $before['message'])->withInput();
        } else {
            $data = $before['data'];
        }

        try {
            DB::beginTransaction();
            
            // Extract atribut data
            $atributData = $data['atribut'] ?? [];
            unset($data['atribut']);

            // Create pengajuan surat
            $pengajuanSurat = $this->repository->create($data);

            // Save atribut values
            foreach ($atributData as $atributId => $atributValue) {
                $nilai = $atributValue['nilai'] ?? null;
                $lampiranFiles = $atributValue['lampiran_files'] ?? [];

                // Handle file uploads for lampiran
                $uploadedFiles = [];
                if (!empty($lampiranFiles)) {
                    foreach ($lampiranFiles as $file) {
                        if ($file && $file->isValid()) {
                            $path = $file->store('pengajuan-surat/lampiran', 'public');
                            $uploadedFiles[] = $path;
                        }
                    }
                }

                PengajuanSuratAtribut::create([
                    'pengajuan_surat_id' => $pengajuanSurat->id,
                    'atribut_jenis_surat_id' => $atributId,
                    'nilai' => $nilai,
                    'lampiran_files' => !empty($uploadedFiles) ? json_encode($uploadedFiles) : null,
                ]);
            }

            DB::commit();
            
            // Redirect berdasarkan route yang digunakan (pengajuan-saya atau pengajuan-surat)
            if ($isPengajuanSaya) {
                // Dari pengajuan-saya, redirect ke pengajuan-saya
                return redirect()->route('pengajuan-saya.index')->with('success', 'Pengajuan surat berhasil dibuat.');
            } else {
                // Dari pengajuan-surat (admin), redirect ke pengajuan-surat
                return redirect()->route('pengajuan-surat.index')->with('success', 'Pengajuan surat berhasil dibuat.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal membuat pengajuan surat: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Update pengajuan surat (hanya jika status menunggu atau ditolak)
     */
    public function update(Request $request, $id)
    {
        $this->request = $request;
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        
        // Validate first
        $validated = $this->request->validate($this->getValidationRules());
        
        // Get all data including files
        $data = $this->request->all();
        $data['id'] = $id;
        

        // Check if pengajuan can be edited
        $pengajuan = $this->repository->getById($id);
        if (!$pengajuan->canBeEdited()) {
            return redirect()->back()->with('error', 'Pengajuan surat tidak dapat diedit karena sudah disetujui.')->withInput();
        }

        // Check if this is from pengajuan-saya route (warga) - ensure they can only edit their own
        $user = Auth::user();
        $isPengajuanSaya = $request->routeIs('pengajuan-saya.*');
        if ($isPengajuanSaya && $user && $pengajuan->created_by != $user->id) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit pengajuan ini.');
        }

        $before = $this->repository->callbackBeforeStoreOrUpdate($data, 'update');
        if ($before['error'] != 0) {
            return redirect()->back()->with('error', $before['message'])->withInput();
        } else {
            $data = $before['data'];
        }

        try {
            DB::beginTransaction();
            
            // Extract atribut data - handle FormData nested array
            // Laravel automatically parses FormData with nested arrays like atribut[1][nilai]
            $atributData = [];
            
            // First try: Get from FormData nested array (primary method)
            $atributInput = $this->request->input('atribut');
            if (is_array($atributInput)) {
                foreach ($atributInput as $atributId => $atributValue) {
                    if (is_array($atributValue)) {
                        $atributData[$atributId] = [
                            'nilai' => $atributValue['nilai'] ?? null,
                            'lampiran_files' => $atributValue['lampiran_files'] ?? [],
                        ];
                    }
                }
            }
            
            // Second try: Get nilai from JSON backup if FormData parsing failed
            if (empty($atributData)) {
                $atributNilaiJson = $this->request->input('atribut_nilai_json');
                if ($atributNilaiJson) {
                    $decoded = json_decode($atributNilaiJson, true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $atributId => $nilai) {
                            $atributData[$atributId] = [
                                'nilai' => $nilai,
                                'lampiran_files' => [],
                            ];
                        }
                    }
                }
            }
            
            // Third try: Check in $data
            if (empty($atributData) && isset($data['atribut']) && is_array($data['atribut'])) {
                foreach ($data['atribut'] as $atributId => $atributValue) {
                    if (is_array($atributValue)) {
                        $atributData[$atributId] = [
                            'nilai' => $atributValue['nilai'] ?? null,
                            'lampiran_files' => $atributValue['lampiran_files'] ?? [],
                        ];
                    }
                }
            }
            
            unset($data['atribut']);
            unset($data['atribut_nilai_json']);

            // Update status to "diperbaiki" if previous status was "ditolak"
            if ($pengajuan->status === 'ditolak') {
                $data['status'] = 'diperbaiki';
                $data['alasan_penolakan'] = null; // Clear alasan penolakan
            }

            // Ensure updated_at is updated
            $data['updated_at'] = now();

            // Update pengajuan surat
            $pengajuan = $this->repository->update($id, $data);

            // Delete existing atribut (soft delete tidak perlu, langsung delete)
            PengajuanSuratAtribut::where('pengajuan_surat_id', $pengajuan->id)->forceDelete();

            // Save new atribut values
            foreach ($atributData as $atributId => $atributValue) {
                $nilai = $atributValue['nilai'] ?? null;
                $lampiranFiles = $atributValue['lampiran_files'] ?? [];

                // Handle file uploads for lampiran
                $uploadedFiles = [];
                if (!empty($lampiranFiles)) {
                    foreach ($lampiranFiles as $file) {
                        if ($file && is_object($file) && method_exists($file, 'isValid') && $file->isValid()) {
                            $path = $file->store('pengajuan-surat/lampiran', 'public');
                            $uploadedFiles[] = $path;
                        } elseif (is_string($file)) {
                            // If it's a string (existing file path), keep it
                            $uploadedFiles[] = $file;
                        }
                    }
                }
                
                // Keep existing files if no new files uploaded
                if (empty($uploadedFiles) && isset($atributValue['existing_files']) && !empty($atributValue['existing_files'])) {
                    $uploadedFiles = $atributValue['existing_files'];
                }

                PengajuanSuratAtribut::create([
                    'pengajuan_surat_id' => $pengajuan->id,
                    'atribut_jenis_surat_id' => $atributId,
                    'nilai' => $nilai,
                    'lampiran_files' => !empty($uploadedFiles) ? $uploadedFiles : null,
                ]);
            }

            DB::commit();
            
            // Redirect ke show page berdasarkan route yang digunakan
            if ($isPengajuanSaya) {
                // Dari pengajuan-saya, redirect ke pengajuan-saya show
                return redirect()->route('pengajuan-saya.show', $pengajuan->id)->with('success', 'Pengajuan surat berhasil diupdate.');
            } else {
                // Dari pengajuan-surat (admin), redirect ke pengajuan-surat show
                return redirect()->route('pengajuan-surat.show', $pengajuan->id)->with('success', 'Pengajuan surat berhasil diupdate.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mengupdate pengajuan surat: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show verifikasi form
     */
    public function verifikasi($id)
    {
        // Load pengajuan with all necessary relationships
        $pengajuan = $this->repository->getById($id);
        
        if (!in_array($pengajuan->status, ['menunggu', 'diperbaiki'])) {
            return redirect()->route('pengajuan-surat.show', $id)
                ->with('error', 'Pengajuan surat sudah diverifikasi.');
        }

        $data = $this->commonData + [
            'item' => $pengajuan,
        ];

        // Get admin TTD options
        $adminId = Auth::id();
        $data['admin_ttd_digital'] = $this->adminTandaTanganRepository->getTtdByAdminAndType($adminId, 'digital');
        $data['admin_ttd_foto'] = $this->adminTandaTanganRepository->getTtdByAdminAndType($adminId, 'foto');

        // Use customShow to get atribut_detail
        $data = $this->repository->customShow($data, $pengajuan);
        return inertia("modules/{$this->route}/Verifikasi", $data);
    }

    /**
     * Store verifikasi
     */
    public function storeVerifikasi(VerifikasiPengajuanSuratRequest $request)
    {
        try {
            DB::beginTransaction();

            $pengajuan = $this->repository->getById($request->id);
            
            if (!in_array($pengajuan->status, ['menunggu', 'diperbaiki'])) {
                return redirect()->back()->with('error', 'Pengajuan surat sudah diverifikasi.');
            }

            $adminId = Auth::id();
            $data = [
                'status' => $request->status,
                'admin_verifikasi_id' => $adminId,
            ];

            if ($request->status === 'disetujui') {
                $data['tanggal_disetujui'] = now();
                
                // Handle tanda tangan
                if ($request->tanda_tangan_type === 'digital') {
                    if ($request->use_existing_ttd === 'yes') {
                        // Use existing TTD from admin_tanda_tangan
                        $adminTtd = $this->adminTandaTanganRepository->getTtdByAdminAndType($adminId, 'digital');
                        if ($adminTtd && $adminTtd->tanda_tangan_digital) {
                            $data['tanda_tangan_digital'] = $adminTtd->tanda_tangan_digital;
                            $data['tanda_tangan_type'] = 'digital';
                        }
                    } else {
                        // Save new TTD digital (base64 string from canvas)
                        if ($request->tanda_tangan_digital) {
                            // Handle as base64 string (from canvas toDataURL)
                            $ttdData = $request->tanda_tangan_digital;
                            // Ensure it's in correct format
                            if (!str_starts_with($ttdData, 'data:')) {
                                $ttdData = 'data:image/png;base64,' . $ttdData;
                            }
                            $data['tanda_tangan_digital'] = $ttdData;
                            $data['tanda_tangan_type'] = 'digital';

                            // Save to admin_tanda_tangan if not exists
                            $adminTtd = $this->adminTandaTanganRepository->getOrCreateTtd($adminId, 'digital');
                            if (!$adminTtd->tanda_tangan_digital) {
                                $adminTtd->update(['tanda_tangan_digital' => $ttdData]);
                            }
                        }
                    }
                } else {
                    // Handle foto TTD
                    if ($request->use_existing_ttd === 'yes') {
                        // Use existing TTD from admin_tanda_tangan
                        $adminTtd = $this->adminTandaTanganRepository->getTtdByAdminAndType($adminId, 'foto');
                        if ($adminTtd && $adminTtd->foto_tanda_tangan) {
                            $data['foto_tanda_tangan'] = $adminTtd->foto_tanda_tangan;
                            $data['tanda_tangan_type'] = 'foto';
                        }
                    } else {
                        // Upload new foto TTD
                        if ($request->hasFile('foto_tanda_tangan')) {
                            $file = $request->file('foto_tanda_tangan');
                            $path = $file->store('admin-tanda-tangan', 'public');
                            $data['foto_tanda_tangan'] = $path;
                            $data['tanda_tangan_type'] = 'foto';

                            // Save to admin_tanda_tangan if not exists
                            $adminTtd = $this->adminTandaTanganRepository->getOrCreateTtd($adminId, 'foto');
                            if (!$adminTtd->foto_tanda_tangan) {
                                $adminTtd->update(['foto_tanda_tangan' => $path]);
                            }
                        }
                    }
                }

                // Generate nomor surat
                $data['nomor_surat'] = $this->repository->generateNomorSurat($pengajuan);
            } else {
                $data['alasan_penolakan'] = $request->alasan_penolakan;
            }

            $pengajuan->update($data);

            DB::commit();
            return redirect()->route('pengajuan-surat.show', $pengajuan->id)
                ->with('success', 'Pengajuan surat berhasil diverifikasi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memverifikasi pengajuan surat: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Preview PDF
     */
    public function previewPdf($id)
    {
        $pengajuan = $this->repository->getById($id);
        
        if ($pengajuan->status !== 'disetujui') {
            return redirect()->route('pengajuan-surat.show', $id)
                ->with('error', 'Surat belum disetujui.');
        }

        // Load relationships
        $pengajuan->load(['jenisSurat', 'resident', 'adminVerifikasi.role']);

        // Get atribut detail
        $data = $this->repository->customShow([], $pengajuan);
        $atribut_detail = $data['atribut_detail'] ?? [];

        $pdf = Pdf::loadView('pdf.pengajuan-surat', [
            'pengajuan' => $pengajuan,
            'atribut_detail' => $atribut_detail,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('surat-' . str_replace('/', '-', $pengajuan->nomor_surat ?? 'preview') . '.pdf');
    }

    /**
     * Export PDF
     */
    public function exportPdf($id)
    {
        $pengajuan = $this->repository->getById($id);
        
        if ($pengajuan->status !== 'disetujui') {
            return redirect()->route('pengajuan-surat.show', $id)
                ->with('error', 'Surat belum disetujui.');
        }

        // Load relationships
        $pengajuan->load(['jenisSurat', 'resident', 'adminVerifikasi.role']);

        // Get atribut detail
        $data = $this->repository->customShow([], $pengajuan);
        $atribut_detail = $data['atribut_detail'] ?? [];

        $pdf = Pdf::loadView('pdf.pengajuan-surat', [
            'pengajuan' => $pengajuan,
            'atribut_detail' => $atribut_detail,
        ])->setPaper('a4', 'portrait');

        $filename = 'surat-' . str_replace('/', '-', $pengajuan->nomor_surat ?? 'export') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Destroy pengajuan surat
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            // Delete atribut terkait terlebih dahulu
            PengajuanSuratAtribut::where('pengajuan_surat_id', $id)->forceDelete();
            
            // Delete pengajuan surat
            $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
            $model = $this->repository->delete($id);
            $callback = $this->repository->callbackAfterDelete($model, $id);
            
            if (!($callback instanceof \Illuminate\Database\Eloquent\Model)) {
                DB::rollBack();
                return $callback;
            }
            
            DB::commit();
            return redirect()->route('pengajuan-surat.index')->with('success', trans('message.success_delete'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus pengajuan surat: ' . $e->getMessage());
        }
    }

    protected function getValidationRules()
    {
        if (method_exists($this->request, 'rules')) {
            return $this->request->rules();
        }
        return [];
    }
}

