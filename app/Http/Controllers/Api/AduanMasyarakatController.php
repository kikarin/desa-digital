<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\AduanMasyarakatRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AduanMasyarakatController extends Controller
{
    protected $repository;

    public function __construct(AduanMasyarakatRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get list aduan saya (hanya aduan milik user yang login)
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

            // Gunakan repository dengan filter by created_by = true
            $data = $this->repository->customIndex([], true);
            
            return response()->json([
                'success' => true,
                'data' => $data['aduan_masyarakat'] ?? [],
                'meta' => $data['meta'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data aduan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detail aduan saya
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

            $item = $this->repository->getById($id);
            
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aduan tidak ditemukan',
                ], 404);
            }

            // Validasi ownership - hanya bisa akses aduan sendiri
            if ($item->created_by !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke aduan ini',
                ], 403);
            }

            $data = ['item' => $item];
            $data = $this->repository->customShow($data, $item);
            
            return response()->json([
                'success' => true,
                'data' => $data['item'] ?? $item,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data aduan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create aduan baru
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

            // Validasi input
            $validator = Validator::make($request->all(), [
                'kategori_aduan_id' => 'required|exists:mst_kategori_aduan,id',
                'judul' => 'required|string|max:255',
                'detail_aduan' => 'required|string',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'nama_lokasi' => 'nullable|string|max:255',
                'kecamatan_id' => 'nullable|exists:mst_kecamatan,id',
                'desa_id' => 'nullable|exists:mst_desa,id',
                'deskripsi_lokasi' => 'nullable|string',
                'jenis_aduan' => 'required|in:publik,private',
                'alasan_melaporkan' => 'nullable|string',
                'files' => 'nullable|array',
                'files.*' => 'file|mimes:jpeg,jpg,png,gif,mp4,mov,avi|max:10240', // Max 10MB
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $request->all();
            
            // Set status default
            if (!isset($data['status'])) {
                $data['status'] = 'menunggu_verifikasi';
            }

            // Callback sebelum create
            $before = $this->repository->callbackBeforeStoreOrUpdate($data, 'store');
            if ($before['error'] != 0) {
                return response()->json([
                    'success' => false,
                    'message' => $before['message'] ?? 'Validasi gagal',
                ], 400);
            }

            $data = $before['data'];
            
            // Create aduan
            $model = $this->repository->create($data);

            // Handle file upload jika ada
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                if (!is_array($files)) {
                    $files = [$files];
                }
                $this->repository->storeFiles($model, $files);
            }

            // Reload dengan relasi
            $model->load(['kategori_aduan', 'kecamatan', 'desa', 'created_by_user', 'updated_by_user', 'files']);
            $data = ['item' => $model];
            $data = $this->repository->customShow($data, $model);

            return response()->json([
                'success' => true,
                'message' => 'Aduan berhasil dibuat',
                'data' => $data['item'] ?? $model,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat aduan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update aduan saya
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // FIX: Untuk PUT request dengan multipart/form-data, Laravel tidak membaca dengan benar
            // Kita perlu merge data dari $_POST dan $_FILES langsung
            if ($request->method() === 'PUT' && str_contains($request->header('Content-Type', ''), 'multipart/form-data')) {
                // Merge dengan $_POST
                $postData = $_POST ?? [];
                
                // Merge POST data ke request
                foreach ($postData as $key => $value) {
                    // Handle array notation (deleted_files[])
                    if (str_ends_with($key, '[]')) {
                        $baseKey = rtrim($key, '[]');
                        if (!$request->has($baseKey)) {
                            $request->merge([$baseKey => []]);
                        }
                        $existing = $request->input($baseKey, []);
                        if (!is_array($existing)) {
                            $existing = [$existing];
                        }
                        $existing[] = $value;
                        $request->merge([$baseKey => $existing]);
                    } else {
                        if (!$request->has($key)) {
                            $request->merge([$key => $value]);
                        }
                    }
                }
                
                // Handle files dari $_FILES
                $filesData = $_FILES ?? [];
                
                if (!empty($filesData)) {
                    foreach ($filesData as $key => $fileData) {
                        // Handle single file
                        if (isset($fileData['tmp_name']) && !is_array($fileData['tmp_name'])) {
                            if (is_uploaded_file($fileData['tmp_name'])) {
                                $uploadedFile = \Illuminate\Http\UploadedFile::createFromBase(
                                    new \Symfony\Component\HttpFoundation\File\UploadedFile(
                                        $fileData['tmp_name'],
                                        $fileData['name'],
                                        $fileData['type'],
                                        $fileData['error'],
                                        true
                                    )
                                );
                                
                                // Handle array notation (files[])
                                if (str_ends_with($key, '[]')) {
                                    $baseKey = rtrim($key, '[]');
                                    $existing = $request->file($baseKey);
                                    if (!$existing) {
                                        $request->files->set($baseKey, [$uploadedFile]);
                                    } else {
                                        if (!is_array($existing)) {
                                            $existing = [$existing];
                                        }
                                        $existing[] = $uploadedFile;
                                        $request->files->set($baseKey, $existing);
                                    }
                                } else {
                                    $request->files->set($key, $uploadedFile);
                                }
                            }
                        }
                        // Handle multiple files
                        elseif (isset($fileData['tmp_name']) && is_array($fileData['tmp_name'])) {
                            $files = [];
                            foreach ($fileData['tmp_name'] as $index => $tmpName) {
                                if (is_uploaded_file($tmpName)) {
                                    $files[] = \Illuminate\Http\UploadedFile::createFromBase(
                                        new \Symfony\Component\HttpFoundation\File\UploadedFile(
                                            $tmpName,
                                            $fileData['name'][$index],
                                            $fileData['type'][$index],
                                            $fileData['error'][$index],
                                            true
                                        )
                                    );
                                }
                            }
                            if (!empty($files)) {
                                $baseKey = str_ends_with($key, '[]') ? rtrim($key, '[]') : $key;
                                $request->files->set($baseKey, $files);
                            }
                        }
                    }
                }
            }
            
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi',
                ], 401);
            }

            $item = $this->repository->getById($id);
            
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aduan tidak ditemukan',
                ], 404);
            }

            // Validasi ownership
            if ($item->created_by !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke aduan ini',
                ], 403);
            }

            // Cek status - aduan yang sudah selesai tidak bisa diedit
            if ($item->status === 'selesai') {
                return response()->json([
                    'success' => false,
                    'message' => 'Aduan yang sudah selesai tidak dapat diedit',
                ], 400);
            }

            // Validasi input
            $validator = Validator::make($request->all(), [
                'kategori_aduan_id' => 'sometimes|required|exists:mst_kategori_aduan,id',
                'judul' => 'sometimes|required|string|max:255',
                'detail_aduan' => 'sometimes|required|string',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'nama_lokasi' => 'nullable|string|max:255',
                'kecamatan_id' => 'nullable|exists:mst_kecamatan,id',
                'desa_id' => 'nullable|exists:mst_desa,id',
                'deskripsi_lokasi' => 'nullable|string',
                'jenis_aduan' => 'sometimes|required|in:publik,private',
                'alasan_melaporkan' => 'nullable|string',
                'files' => 'nullable|array',
                'files.*' => 'file|mimes:jpeg,jpg,png,gif,mp4,mov,avi|max:10240',
                'deleted_files' => 'nullable|array',
                'deleted_files.*' => 'integer|exists:aduan_masyarakat_files,id',
            ]);

            // Validasi tambahan: pastikan deleted_files milik aduan ini
            if ($request->has('deleted_files') && is_array($request->deleted_files)) {
                $invalidFiles = \App\Models\AduanMasyarakatFile::whereIn('id', $request->deleted_files)
                    ->where('aduan_masyarakat_id', '!=', $id)
                    ->pluck('id')
                    ->toArray();
                
                if (!empty($invalidFiles)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Beberapa file yang akan dihapus tidak valid',
                        'errors' => ['deleted_files' => 'File dengan ID: ' . implode(', ', $invalidFiles) . ' tidak milik aduan ini'],
                    ], 422);
                }
            }

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $request->all();

            // Callback sebelum update
            $before = $this->repository->callbackBeforeStoreOrUpdate($data, 'update');
            if ($before['error'] != 0) {
                return response()->json([
                    'success' => false,
                    'message' => $before['message'] ?? 'Validasi gagal',
                ], 400);
            }

            $data = $before['data'];

            // Pastikan deleted_files ada di data untuk diproses di customDataCreateUpdate
            if ($request->has('deleted_files')) {
                $data['deleted_files'] = $request->input('deleted_files', []);
            }

            // Update aduan (customDataCreateUpdate akan memproses deleted_files)
            $model = $this->repository->update($id, $data);

            // Handle file upload jika ada
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                if (!is_array($files)) {
                    $files = [$files];
                }
                $this->repository->storeFiles($model, $files);
            }

            // Reload dengan relasi untuk mendapatkan data terbaru
            $model->refresh();
            $model->load(['kategori_aduan', 'kecamatan', 'desa', 'created_by_user', 'updated_by_user', 'files']);
            $data = ['item' => $model];
            $data = $this->repository->customShow($data, $model);

            return response()->json([
                'success' => true,
                'message' => 'Aduan berhasil diupdate',
                'data' => $data['item'] ?? $model,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate aduan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update aduan dengan file (menggunakan POST karena PUT tidak support multipart/form-data dengan baik)
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateWithFiles(Request $request, $id)
    {
        // Panggil method update yang sama, tapi dengan POST method
        // POST method akan membaca multipart/form-data dengan benar
        return $this->update($request, $id);
    }

    /**
     * Delete aduan saya
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

            $item = $this->repository->getById($id);
            
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aduan tidak ditemukan',
                ], 404);
            }

            // Validasi ownership
            if ($item->created_by !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke aduan ini',
                ], 403);
            }

            // Delete aduan
            $this->repository->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Aduan berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus aduan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list kategori aduan untuk dropdown
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKategoriAduan()
    {
        try {
            $kategoris = \App\Models\KategoriAduan::select('id', 'nama')
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
                'message' => 'Gagal mengambil data kategori aduan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list kecamatan untuk dropdown
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKecamatan()
    {
        try {
            $kecamatans = \App\Models\Kecamatan::select('id', 'nama')
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
                'data' => $kecamatans,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kecamatan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list desa/kelurahan berdasarkan kecamatan_id
     * 
     * @param int $kecamatanId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDesa($kecamatanId)
    {
        try {
            $desas = \App\Models\Desa::where('id_kecamatan', $kecamatanId)
                ->select('id', 'nama')
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
                'data' => $desas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data desa',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

