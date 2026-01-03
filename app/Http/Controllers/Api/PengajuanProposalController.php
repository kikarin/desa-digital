<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\PengajuanProposalRepository;
use App\Models\PengajuanProposal;
use App\Models\KategoriProposal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PengajuanProposalController extends Controller
{
    protected $repository;

    public function __construct(PengajuanProposalRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get list kategori proposal
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKategoriProposal()
    {
        try {
            $kategoris = KategoriProposal::select('id', 'nama')
                ->orderBy('nama', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'value' => $item->id,
                        'label' => $item->nama,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $kategoris,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kategori proposal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list pengajuan proposal saya (hanya pengajuan milik user yang login)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi',
                ], 401);
            }

            // Filter by created_by untuk warga (hanya menampilkan pengajuan yang dibuat oleh user yang login)
            $data = $this->repository->customIndex(['filter_created_by' => $user->id], false);
            
            return response()->json([
                'success' => true,
                'data' => $data['pengajuan_proposal'] ?? [],
                'meta' => $data['meta'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pengajuan proposal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detail pengajuan proposal saya
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi',
                ], 401);
            }

            $pengajuan = $this->repository->getById($id);
            
            if (!$pengajuan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan proposal tidak ditemukan',
                ], 404);
            }

            // Validasi ownership - hanya bisa akses pengajuan sendiri
            if ($pengajuan->created_by !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke pengajuan ini',
                ], 403);
            }

            $data = $this->repository->customShow([], $pengajuan);
            $item = $data['item'] ?? [];
            
            // Transform file paths dan thumbnail
            if (isset($item['file_pendukung']) && is_array($item['file_pendukung'])) {
                $item['file_pendukung'] = array_map(function ($file) {
                    return [
                        'path' => asset('storage/' . $file),
                        'name' => basename($file),
                    ];
                }, $item['file_pendukung']);
            }
            
            if (isset($item['thumbnail_foto_banner']) && $item['thumbnail_foto_banner']) {
                // Jika belum full URL, tambahkan asset()
                if (!str_starts_with($item['thumbnail_foto_banner'], 'http')) {
                    $item['thumbnail_foto_banner'] = asset('storage/' . $item['thumbnail_foto_banner']);
                }
            }
            
            // Format tanggal dengan timezone
            if (isset($item['created_at'])) {
                $item['created_at'] = Carbon::parse($item['created_at'])->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            }
            if (isset($item['updated_at'])) {
                $item['updated_at'] = Carbon::parse($item['updated_at'])->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            }
            if (isset($item['tanggal_diverifikasi'])) {
                $item['tanggal_diverifikasi'] = Carbon::parse($item['tanggal_diverifikasi'])->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            }
            
            return response()->json([
                'success' => true,
                'data' => $item,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pengajuan proposal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create pengajuan proposal baru
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi',
                ], 401);
            }

            // Auto-set resident_id dari user yang login
            if (!$user->resident_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak memiliki data resident. Silakan lengkapi profil terlebih dahulu.',
                ], 400);
            }

            // Validasi input
            $validator = Validator::make($request->all(), [
                'kategori_proposal_id' => 'required|exists:mst_kategori_proposal,id',
                'nomor_telepon_pengaju' => 'required|string|max:20',
                'nama_kegiatan' => 'required|string|max:255',
                'deskripsi_kegiatan' => 'required|string',
                'usulan_anggaran' => 'required|numeric|min:0',
                'file_pendukung' => 'nullable|array',
                'file_pendukung.*' => 'file|mimes:pdf,doc,docx,xlsx,xls|max:10240',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'nama_lokasi' => 'nullable|string|max:255',
                'alamat' => 'nullable|string',
                'thumbnail_foto_banner' => 'nullable|file|image|mimes:jpeg,jpg,png,gif|max:5120',
                'tanda_tangan_digital' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            // Prepare data
            $data = $request->all();
            $data['resident_id'] = $user->resident_id; // Auto-set dari user
            $data['status'] = 'menunggu_verifikasi';

            // Callback sebelum create
            $before = $this->repository->callbackBeforeStoreOrUpdate($data, 'store');
            if ($before['error'] != 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $before['message'] ?? 'Validasi gagal',
                ], 400);
            }

            $data = $before['data'];

            // Create pengajuan proposal
            $pengajuanProposal = $this->repository->create($data);

            DB::commit();

            // Reload dengan relasi
            $pengajuanProposal->load(['kategoriProposal', 'resident', 'created_by_user']);
            $data = $this->repository->customShow([], $pengajuanProposal);

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan proposal berhasil dibuat',
                'data' => $data['item'] ?? $pengajuanProposal,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pengajuan proposal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update pengajuan proposal saya (menggunakan POST karena PUT tidak support multipart/form-data dengan baik)
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi',
                ], 401);
            }

            $pengajuan = $this->repository->getById($id);
            
            if (!$pengajuan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan proposal tidak ditemukan',
                ], 404);
            }

            // Validasi ownership
            if ($pengajuan->created_by !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke pengajuan ini',
                ], 403);
            }

            // Cek status - hanya bisa edit jika status menunggu_verifikasi atau ditolak
            if (!$pengajuan->canBeEdited()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan proposal tidak dapat diedit karena sudah disetujui',
                ], 400);
            }

            // Validasi input
            $validator = Validator::make($request->all(), [
                'kategori_proposal_id' => 'sometimes|required|exists:mst_kategori_proposal,id',
                'nomor_telepon_pengaju' => 'sometimes|required|string|max:20',
                'nama_kegiatan' => 'sometimes|required|string|max:255',
                'deskripsi_kegiatan' => 'sometimes|required|string',
                'usulan_anggaran' => 'sometimes|required|numeric|min:0',
                'file_pendukung' => 'nullable|array',
                'file_pendukung.*' => 'file|mimes:pdf,doc,docx,xlsx,xls|max:10240',
                'deleted_files' => 'nullable|array',
                'deleted_files.*' => 'string',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'nama_lokasi' => 'nullable|string|max:255',
                'alamat' => 'nullable|string',
                'thumbnail_foto_banner' => 'nullable|file|image|mimes:jpeg,jpg,png,gif|max:5120',
                'tanda_tangan_digital' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            // Prepare data
            $data = $request->all();
            
            // Handle deleted files - parse manual dari $_POST untuk FormData nested array
            // Karena Laravel tidak selalu parse nested array dengan benar untuk POST request
            $postData = $_POST ?? [];
            $deletedFiles = [];
            
            // Parse deleted_files secara manual dari $_POST
            foreach ($postData as $key => $value) {
                // Match pattern: deleted_files[]
                if (preg_match('/^deleted_files\[\]$/', $key)) {
                    $deletedFiles[] = $value;
                }
            }
            
            // Jika masih kosong, coba dari request input (fallback)
            if (empty($deletedFiles)) {
                $deletedFilesInput = $request->input('deleted_files', []);
                if (is_array($deletedFilesInput)) {
                    $deletedFiles = $deletedFilesInput;
                } elseif (!empty($deletedFilesInput)) {
                    $deletedFiles = [$deletedFilesInput];
                }
            }
            
            $existingFiles = $pengajuan->file_pendukung ?? [];
            
            if (is_string($existingFiles)) {
                $existingFiles = json_decode($existingFiles, true) ?? [];
            }
            
            // Remove deleted files
            foreach ($deletedFiles as $deletedFile) {
                // deletedFile bisa berupa path lengkap atau hanya filename
                // Cari di existingFiles yang cocok
                $found = false;
                foreach ($existingFiles as $key => $filePath) {
                    // Check jika path sama atau filename sama
                    if ($filePath === $deletedFile || basename($filePath) === basename($deletedFile)) {
                        // Delete file from storage
                        if (Storage::disk('public')->exists($filePath)) {
                            Storage::disk('public')->delete($filePath);
                        }
                        unset($existingFiles[$key]);
                        $found = true;
                        break;
                    }
                }
                
                // Jika tidak ditemukan, tetap coba hapus dari storage (jika path lengkap)
                if (!$found && Storage::disk('public')->exists($deletedFile)) {
                    Storage::disk('public')->delete($deletedFile);
                }
            }
            
            // Set existing files untuk diproses di callback
            $data['existing_files'] = array_values($existingFiles);
            
            // Update status to "menunggu_verifikasi" if previous status was "ditolak"
            if ($pengajuan->status === 'ditolak') {
                $data['status'] = 'menunggu_verifikasi';
            }

            // Callback sebelum update
            $before = $this->repository->callbackBeforeStoreOrUpdate($data, 'update');
            if ($before['error'] != 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $before['message'] ?? 'Validasi gagal',
                ], 400);
            }

            $data = $before['data'];

            // Update pengajuan proposal
            $this->repository->update($id, $data);

            DB::commit();

            // Reload dengan relasi
            $pengajuan->refresh();
            $pengajuan->load(['kategoriProposal', 'resident', 'created_by_user']);
            $data = $this->repository->customShow([], $pengajuan);

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan proposal berhasil diupdate',
                'data' => $data['item'] ?? $pengajuan,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate pengajuan proposal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export PDF pengajuan proposal (hanya jika sudah disetujui)
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function exportPdf(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi',
                ], 401);
            }

            $pengajuan = $this->repository->getById($id);
            
            if (!$pengajuan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan proposal tidak ditemukan',
                ], 404);
            }

            // Validasi ownership
            if ($pengajuan->created_by !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke pengajuan ini',
                ], 403);
            }

            // Cek status - hanya bisa export jika sudah disetujui
            if ($pengajuan->status !== 'disetujui') {
                return response()->json([
                    'success' => false,
                    'message' => 'Proposal belum disetujui. PDF hanya bisa diunduh setelah proposal disetujui.',
                ], 400);
            }

            // Load relationships
            $pengajuan->load(['kategoriProposal', 'resident', 'adminVerifikasi']);

            // Get data untuk PDF
            $data = $this->repository->customShow([], $pengajuan);

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.pengajuan-proposal', [
                'pengajuan' => $pengajuan,
                'item' => $data['item'] ?? [],
            ])->setPaper('a4', 'portrait');

            $filename = 'proposal-' . $pengajuan->id . '.pdf';
            
            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengekspor PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

