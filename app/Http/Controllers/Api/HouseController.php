<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Houses;
use App\Models\Residents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class HouseController extends Controller
{
    /**
     * Get data rumah user saat ini
     */
    public function getMyHouse(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user || !$user->resident_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi atau tidak memiliki data resident',
                ], 401);
            }

            $resident = $user->resident;
            if (!$resident || !$resident->family_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data keluarga tidak ditemukan',
                ], 404);
            }

            $family = $resident->family;
            if (!$family || !$family->house_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data rumah tidak ditemukan',
                ], 404);
            }

            $house = Houses::with(['rt.rw', 'pemilik', 'families.residents'])
                ->find($family->house_id);

            if (!$house) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data rumah tidak ditemukan',
                ], 404);
            }

            // Get anggota keluarga untuk dropdown pemilik
            $anggotaKeluarga = [];
            foreach ($house->families as $fam) {
                foreach ($fam->residents as $res) {
                    $anggotaKeluarga[] = [
                        'id' => $res->id,
                        'nik' => $res->nik,
                        'nama' => $res->nama,
                    ];
                }
            }

            // Format fotos
            $fotos = [];
            if ($house->fotos && is_array($house->fotos)) {
                foreach ($house->fotos as $foto) {
                    $fotos[] = asset('storage/' . $foto);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $house->id,
                    'rt_id' => $house->rt_id,
                    'rt' => [
                        'id' => $house->rt->id ?? null,
                        'nomor_rt' => $house->rt->nomor_rt ?? null,
                        'label' => ($house->rt->nomor_rt ?? '') . ' - RW ' . ($house->rt->rw->nomor_rw ?? '') . ' - ' . ($house->rt->rw->desa ?? ''),
                    ],
                    'nomor_rumah' => $house->nomor_rumah,
                    'pemilik' => [
                        'is_milik_anda' => $house->pemilik_id ? true : false,
                        'pemilik_id' => $house->pemilik_id,
                        'nama_pemilik' => $house->nama_pemilik,
                    ],
                    'keterangan' => $house->keterangan,
                    'latitude' => $house->latitude,
                    'longitude' => $house->longitude,
                    'fotos' => $fotos,
                    'anggota_keluarga' => $anggotaKeluarga,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data rumah',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update data rumah user (POST untuk support multipart/form-data dengan multiple foto)
     */
    public function updateMyHouse(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user || !$user->resident_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi',
                ], 401);
            }

            $resident = $user->resident;
            if (!$resident || !$resident->family_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data keluarga tidak ditemukan',
                ], 404);
            }

            $family = $resident->family;
            if (!$family || !$family->house_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data rumah tidak ditemukan',
                ], 404);
            }

            $house = Houses::with(['rt.rw', 'families.residents'])->find($family->house_id);
            if (!$house) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data rumah tidak ditemukan',
                ], 404);
            }

            // Handle multipart/form-data jika diperlukan
            if (str_contains($request->header('Content-Type', ''), 'multipart/form-data') || !empty($_FILES)) {
                // Merge $_POST data jika ada
                $postData = $_POST ?? [];
                foreach ($postData as $key => $value) {
                    // Handle array notation (deleted_fotos[])
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
            }

            // Validasi
            $validated = $request->validate([
                'nomor_rumah' => 'required|string|max:50',
                'is_milik_anda' => 'required|boolean',
                'pemilik_id' => 'required_if:is_milik_anda,true|nullable|exists:residents,id',
                'keterangan' => 'nullable|string|max:500',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'fotos' => 'nullable|array',
                'fotos.*' => 'image|mimes:jpeg,png,jpg|max:5120', // Max 5MB per foto
                'deleted_fotos' => 'nullable|array', // Array index foto yang akan dihapus
            ], [
                'nomor_rumah.required' => 'Nomor rumah wajib diisi',
                'nomor_rumah.max' => 'Nomor rumah maksimal 50 karakter',
                'is_milik_anda.required' => 'Status kepemilikan wajib dipilih',
                'pemilik_id.required_if' => 'Pemilik wajib dipilih jika rumah milik Anda',
                'pemilik_id.exists' => 'Pemilik tidak ditemukan',
                'keterangan.max' => 'Keterangan maksimal 500 karakter',
                'latitude.between' => 'Latitude harus antara -90 sampai 90',
                'longitude.between' => 'Longitude harus antara -180 sampai 180',
                'fotos.*.image' => 'File harus berupa gambar',
                'fotos.*.mimes' => 'Format file harus JPG, PNG, atau JPEG',
                'fotos.*.max' => 'Ukuran file maksimal 5MB',
            ]);

            DB::beginTransaction();

            // Handle pemilik
            $pemilikId = null;
            $namaPemilik = null;
            
            if ($validated['is_milik_anda']) {
                // Milik Anda - set pemilik_id dari anggota keluarga
                if (isset($validated['pemilik_id'])) {
                    // Validasi pemilik harus dari keluarga yang sama
                    $pemilik = Residents::find($validated['pemilik_id']);
                    if (!$pemilik) {
                        throw new \Exception('Pemilik tidak ditemukan');
                    }
                    
                    // Cek apakah pemilik dari keluarga yang sama (dari house yang sama)
                    $isFromSameHouse = false;
                    foreach ($house->families as $fam) {
                        if ($fam->id === $pemilik->family_id) {
                            $isFromSameHouse = true;
                            break;
                        }
                    }
                    
                    if (!$isFromSameHouse) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Pemilik harus dari anggota keluarga yang sama',
                        ], 400);
                    }
                    
                    $pemilikId = $pemilik->id;
                    $namaPemilik = $pemilik->nama;
                }
            } else {
                // Bukan milik Anda - set null
                $pemilikId = null;
                $namaPemilik = null;
            }

            // Handle foto - hapus foto yang dihapus
            $currentFotos = $house->fotos ?? [];
            if (isset($validated['deleted_fotos']) && is_array($validated['deleted_fotos'])) {
                foreach ($validated['deleted_fotos'] as $index) {
                    if (isset($currentFotos[$index])) {
                        // Hapus file dari storage
                        Storage::disk('public')->delete($currentFotos[$index]);
                        unset($currentFotos[$index]);
                    }
                }
                $currentFotos = array_values($currentFotos); // Re-index array
            }

            // Handle foto baru - upload
            $newFotos = [];
            if ($request->hasFile('fotos')) {
                $files = $request->file('fotos');
                // Handle jika single file atau array
                if (!is_array($files)) {
                    $files = [$files];
                }
                
                foreach ($files as $foto) {
                    if ($foto && $foto->isValid()) {
                        $path = $foto->store('houses/' . $house->id, 'public');
                        $newFotos[] = $path;
                    }
                }
            }

            // Merge foto lama dan baru
            $allFotos = array_merge($currentFotos, $newFotos);

            // Update data rumah
            $house->update([
                'nomor_rumah' => $validated['nomor_rumah'],
                'pemilik_id' => $pemilikId,
                'nama_pemilik' => $namaPemilik,
                'keterangan' => $validated['keterangan'] ?? $house->keterangan,
                'latitude' => $validated['latitude'] ?? $house->latitude,
                'longitude' => $validated['longitude'] ?? $house->longitude,
                'fotos' => !empty($allFotos) ? $allFotos : null,
                'updated_by' => $user->id,
            ]);

            DB::commit();

            // Reload dengan relasi
            $house->refresh();
            $house->load(['rt.rw', 'pemilik']);

            // Format fotos untuk response
            $fotosResponse = [];
            if ($house->fotos && is_array($house->fotos)) {
                foreach ($house->fotos as $foto) {
                    $fotosResponse[] = asset('storage/' . $foto);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Data rumah berhasil diperbarui',
                'data' => [
                    'id' => $house->id,
                    'nomor_rumah' => $house->nomor_rumah,
                    'rt' => [
                        'id' => $house->rt->id ?? null,
                        'nomor_rt' => $house->rt->nomor_rt ?? null,
                        'label' => ($house->rt->nomor_rt ?? '') . ' - RW ' . ($house->rt->rw->nomor_rw ?? '') . ' - ' . ($house->rt->rw->desa ?? ''),
                    ],
                    'pemilik' => [
                        'is_milik_anda' => $house->pemilik_id ? true : false,
                        'pemilik_id' => $house->pemilik_id,
                        'nama_pemilik' => $house->nama_pemilik,
                    ],
                    'keterangan' => $house->keterangan,
                    'latitude' => $house->latitude,
                    'longitude' => $house->longitude,
                    'fotos' => $fotosResponse,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data rumah',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validasi nomor rumah
     * User input nomor rumah, sistem cek apakah sesuai dengan rumah yang terhubung dengan keluarga user
     */
    public function validateNomorRumah(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user || !$user->resident_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi',
                ], 401);
            }

            $request->validate([
                'nomor_rumah' => 'required|string|max:50',
            ]);

            $resident = $user->resident;
            if (!$resident || !$resident->family_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data keluarga tidak ditemukan',
                ], 404);
            }

            $family = $resident->family;
            if (!$family || !$family->house_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data rumah tidak ditemukan',
                ], 404);
            }

            $house = Houses::find($family->house_id);
            if (!$house) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data rumah tidak ditemukan',
                ], 404);
            }

            $nomorRumahInput = trim($request->nomor_rumah);
            $nomorRumahDatabase = trim($house->nomor_rumah);

            $isValid = strcasecmp($nomorRumahInput, $nomorRumahDatabase) === 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'is_valid' => $isValid,
                    'nomor_rumah_input' => $nomorRumahInput,
                    'nomor_rumah_database' => $nomorRumahDatabase,
                    'message' => $isValid 
                        ? 'Nomor rumah sesuai dengan data yang terdaftar' 
                        : 'Nomor rumah tidak sesuai. Nomor rumah yang terdaftar: ' . $nomorRumahDatabase,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan validasi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
