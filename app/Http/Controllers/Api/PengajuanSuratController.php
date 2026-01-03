<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\PengajuanSuratRepository;
use App\Models\PengajuanSurat;
use App\Models\PengajuanSuratAtribut;
use App\Models\JenisSurat;
use App\Models\AtributJenisSurat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class PengajuanSuratController extends Controller
{
    protected $repository;

    public function __construct(PengajuanSuratRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get list jenis surat
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJenisSurat()
    {
        try {
            $jenisSurat = JenisSurat::select('id', 'nama', 'kode')
                ->orderBy('nama', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'value' => $item->id,
                        'label' => $item->nama,
                        'kode' => $item->kode,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $jenisSurat,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data jenis surat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detail jenis surat dengan atributnya (untuk form dinamis)
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJenisSuratDetail($id)
    {
        try {
            $jenisSurat = JenisSurat::with(['atribut' => function($query) {
                $query->orderBy('urutan', 'asc');
            }])->find($id);

            if (!$jenisSurat) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jenis surat tidak ditemukan',
                ], 404);
            }

            $atribut = $jenisSurat->atribut->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama_atribut' => $item->nama_atribut,
                    'tipe_data' => $item->tipe_data,
                    'opsi_pilihan' => $item->opsi_pilihan_array,
                    'is_required' => $item->is_required,
                    'nama_lampiran' => $item->nama_lampiran,
                    'minimal_file' => $item->minimal_file,
                    'is_required_lampiran' => $item->is_required_lampiran,
                    'urutan' => $item->urutan,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $jenisSurat->id,
                    'nama' => $jenisSurat->nama,
                    'kode' => $jenisSurat->kode,
                    'atribut' => $atribut,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data jenis surat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list pengajuan surat saya (hanya pengajuan milik user yang login)
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
            $data = $this->repository->customIndex(['filter_created_by' => $user->id]);
            
            return response()->json([
                'success' => true,
                'data' => $data['pengajuan_surat'] ?? [],
                'meta' => $data['meta'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pengajuan surat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detail pengajuan surat saya
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
                    'message' => 'Pengajuan surat tidak ditemukan',
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
            
            // Transform atribut untuk response
            $atributDetail = [];
            if (isset($data['atribut_detail'])) {
                foreach ($data['atribut_detail'] as $atribut) {
                    $lampiranFiles = [];
                    if (!empty($atribut['lampiran_files'])) {
                        foreach ($atribut['lampiran_files'] as $file) {
                            $lampiranFiles[] = [
                                'path' => asset('storage/' . $file),
                                'name' => basename($file),
                            ];
                        }
                    }
                    
                    $atributDetail[] = [
                        'id' => $atribut['id'],
                        'atribut_jenis_surat_id' => $atribut['atribut_jenis_surat_id'],
                        'atribut_nama' => $atribut['atribut_nama'],
                        'atribut_tipe' => $atribut['atribut_tipe'],
                        'nilai' => $atribut['nilai'],
                        'lampiran_files' => $lampiranFiles,
                    ];
                }
            }
            
            $item = $data['item'] ?? [];
            $item['atribut_detail'] = $atributDetail;
            
            return response()->json([
                'success' => true,
                'data' => $item,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pengajuan surat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create pengajuan surat baru
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
                'jenis_surat_id' => 'required|exists:jenis_surat,id',
                'tanggal_surat' => 'required|date',
                'atribut' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Validasi atribut berdasarkan jenis surat
            $jenisSurat = JenisSurat::with('atribut')->find($request->jenis_surat_id);
            if (!$jenisSurat) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jenis surat tidak ditemukan',
                ], 404);
            }

            // Validasi setiap atribut
            foreach ($jenisSurat->atribut as $atribut) {
                $atributKey = $atribut->id;
                $atributValue = $request->input("atribut.{$atributKey}");
                
                // Validasi required
                if ($atribut->is_required && empty($atributValue['nilai'])) {
                    return response()->json([
                        'success' => false,
                        'message' => "Atribut '{$atribut->nama_atribut}' wajib diisi",
                        'errors' => ["atribut.{$atributKey}.nilai" => ["Atribut '{$atribut->nama_atribut}' wajib diisi"]],
                    ], 422);
                }

                // Validasi lampiran jika required
                if ($atribut->is_required_lampiran) {
                    $lampiranFiles = $request->file("atribut.{$atributKey}.lampiran_files");
                    if (empty($lampiranFiles) || (is_array($lampiranFiles) && count($lampiranFiles) < $atribut->minimal_file)) {
                        return response()->json([
                            'success' => false,
                            'message' => "Lampiran '{$atribut->nama_lampiran}' wajib diupload (minimal {$atribut->minimal_file} file)",
                            'errors' => ["atribut.{$atributKey}.lampiran_files" => ["Lampiran '{$atribut->nama_lampiran}' wajib diupload"]],
                        ], 422);
                    }
                }
            }

            DB::beginTransaction();

            // Create pengajuan surat
            $pengajuanData = [
                'jenis_surat_id' => $request->jenis_surat_id,
                'resident_id' => $user->resident_id, // Auto-set dari user
                'tanggal_surat' => $request->tanggal_surat,
                'status' => 'menunggu',
            ];

            $pengajuanSurat = $this->repository->create($pengajuanData);

            // Save atribut values
            $atributData = $request->input('atribut', []);
            foreach ($atributData as $atributId => $atributValue) {
                $nilai = $atributValue['nilai'] ?? null;
                $lampiranFiles = $request->file("atribut.{$atributId}.lampiran_files");

                // Handle file uploads for lampiran
                $uploadedFiles = [];
                if ($lampiranFiles) {
                    if (!is_array($lampiranFiles)) {
                        $lampiranFiles = [$lampiranFiles];
                    }
                    
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

            // Reload dengan relasi
            $pengajuanSurat->load(['jenisSurat', 'resident', 'atribut.atributJenisSurat']);
            $data = $this->repository->customShow([], $pengajuanSurat);

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan surat berhasil dibuat',
                'data' => $data['item'] ?? $pengajuanSurat,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pengajuan surat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update pengajuan surat saya
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
                    'message' => 'Pengajuan surat tidak ditemukan',
                ], 404);
            }

            // Validasi ownership
            if ($pengajuan->created_by !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke pengajuan ini',
                ], 403);
            }

            // Cek status - hanya bisa edit jika status menunggu, ditolak, atau diperbaiki
            if (!$pengajuan->canBeEdited()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan surat tidak dapat diedit karena sudah disetujui',
                ], 400);
            }

            // Validasi input
            $validator = Validator::make($request->all(), [
                'tanggal_surat' => 'sometimes|required|date',
                'atribut' => 'sometimes|required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            // Update pengajuan surat
            $updateData = [];
            if ($request->has('tanggal_surat')) {
                $updateData['tanggal_surat'] = $request->tanggal_surat;
            }

            if (!empty($updateData)) {
                $this->repository->update($id, $updateData);
            }

            // Update atribut jika ada
            if ($request->has('atribut')) {
                $atributData = $request->input('atribut', []);
                
                // Handle manual parsing untuk FormData nested array (khusus untuk deleted_files)
                // Karena Laravel tidak selalu parse nested array dengan benar untuk POST request
                $postData = $_POST ?? [];
                
                foreach ($atributData as $atributId => $atributValue) {
                    $nilai = $atributValue['nilai'] ?? null;
                    $lampiranFiles = $request->file("atribut.{$atributId}.lampiran_files");
                    
                    // Parse deleted_files secara manual dari $_POST
                    $deletedFiles = [];
                    foreach ($postData as $key => $value) {
                        // Match pattern: atribut[{atributId}][deleted_files][]
                        if (preg_match('/^atribut\[(\d+)\]\[deleted_files\]\[\]$/', $key, $matches)) {
                            if ($matches[1] == $atributId) {
                                $deletedFiles[] = $value;
                            }
                        }
                    }
                    
                    // Jika masih kosong, coba dari atributValue (fallback)
                    if (empty($deletedFiles) && isset($atributValue['deleted_files'])) {
                        $deletedFiles = is_array($atributValue['deleted_files']) 
                            ? $atributValue['deleted_files'] 
                            : [$atributValue['deleted_files']];
                    }

                    // Get existing atribut
                    $existingAtribut = PengajuanSuratAtribut::where('pengajuan_surat_id', $id)
                        ->where('atribut_jenis_surat_id', $atributId)
                        ->first();

                    // Handle deleted files
                    $existingFiles = [];
                    if ($existingAtribut && $existingAtribut->lampiran_files) {
                        $decoded = is_string($existingAtribut->lampiran_files) 
                            ? json_decode($existingAtribut->lampiran_files, true) 
                            : $existingAtribut->lampiran_files;
                        $existingFiles = is_array($decoded) ? $decoded : [];
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

                    // Handle new file uploads
                    $uploadedFiles = array_values($existingFiles); // Re-index array
                    if ($lampiranFiles) {
                        if (!is_array($lampiranFiles)) {
                            $lampiranFiles = [$lampiranFiles];
                        }
                        
                        foreach ($lampiranFiles as $file) {
                            if ($file && $file->isValid()) {
                                $path = $file->store('pengajuan-surat/lampiran', 'public');
                                $uploadedFiles[] = $path;
                            }
                        }
                    }

                    // Update or create atribut
                    if ($existingAtribut) {
                        $existingAtribut->update([
                            'nilai' => $nilai,
                            'lampiran_files' => !empty($uploadedFiles) ? json_encode($uploadedFiles) : null,
                        ]);
                    } else {
                        PengajuanSuratAtribut::create([
                            'pengajuan_surat_id' => $id,
                            'atribut_jenis_surat_id' => $atributId,
                            'nilai' => $nilai,
                            'lampiran_files' => !empty($uploadedFiles) ? json_encode($uploadedFiles) : null,
                        ]);
                    }
                }
            }

            DB::commit();

            // Reload dengan relasi
            $pengajuan->refresh();
            $pengajuan->load(['jenisSurat', 'resident', 'atribut.atributJenisSurat']);
            $data = $this->repository->customShow([], $pengajuan);

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan surat berhasil diupdate',
                'data' => $data['item'] ?? $pengajuan,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate pengajuan surat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete pengajuan surat saya
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
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
                    'message' => 'Pengajuan surat tidak ditemukan',
                ], 404);
            }

            // Validasi ownership
            if ($pengajuan->created_by !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke pengajuan ini',
                ], 403);
            }

            // Cek status - hanya bisa hapus jika belum disetujui
            if ($pengajuan->status === 'disetujui') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan surat yang sudah disetujui tidak dapat dihapus',
                ], 400);
            }

            // Delete lampiran files
            foreach ($pengajuan->atribut as $atribut) {
                if ($atribut->lampiran_files) {
                    $files = is_string($atribut->lampiran_files) 
                        ? json_decode($atribut->lampiran_files, true) 
                        : $atribut->lampiran_files;
                    
                    if (is_array($files)) {
                        foreach ($files as $file) {
                            if (Storage::disk('public')->exists($file)) {
                                Storage::disk('public')->delete($file);
                            }
                        }
                    }
                }
            }

            // Delete pengajuan
            $this->repository->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan surat berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pengajuan surat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export PDF pengajuan surat (hanya jika sudah disetujui)
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
                    'message' => 'Pengajuan surat tidak ditemukan',
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
                    'message' => 'Surat belum disetujui. PDF hanya bisa diunduh setelah surat disetujui.',
                ], 400);
            }

            // Load relationships
            $pengajuan->load(['jenisSurat', 'resident', 'adminVerifikasi']);

            // Get atribut detail
            $data = $this->repository->customShow([], $pengajuan);
            $atribut_detail = $data['atribut_detail'] ?? [];

            $pdf = Pdf::loadView('pdf.pengajuan-surat', [
                'pengajuan' => $pengajuan,
                'atribut_detail' => $atribut_detail,
            ])->setPaper('a4', 'portrait');

            $filename = 'surat-' . str_replace('/', '-', $pengajuan->nomor_surat ?? 'export') . '.pdf';
            
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

