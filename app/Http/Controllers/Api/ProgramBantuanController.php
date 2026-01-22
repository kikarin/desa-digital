<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\AssistanceRecipientsRepository;
use App\Models\AssistanceRecipient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProgramBantuanController extends Controller
{
    protected $repository;

    public function __construct(AssistanceRecipientsRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get list program bantuan riwayat saya (hanya program yang terdaftar untuk user)
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

            // Auto-set resident_id dari user
            if (!$user->resident_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak memiliki data resident. Silakan lengkapi profil terlebih dahulu.',
                ], 400);
            }

            // Load resident dengan family
            $resident = $user->resident;
            if (!$resident) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data resident tidak ditemukan',
                ], 404);
            }

            $residentId = $resident->id;
            $familyId = $resident->family_id;

            // Query AssistanceRecipient dengan filter INDIVIDU atau KELUARGA
            $query = AssistanceRecipient::with([
                'program',
                'program.program_items.item',
                'kepala_keluarga',
                'penerima_lapangan',
                'family',
                'resident.family'
            ])
            ->whereNull('assistance_recipients.deleted_at')
            ->where(function($q) use ($residentId, $familyId) {
                // Program INDIVIDU untuk user ini
                $q->where(function($q2) use ($residentId) {
                    $q2->where('target_type', 'INDIVIDU')
                       ->where('resident_id', $residentId);
                })
                // Program KELUARGA untuk keluarga user ini
                ->orWhere(function($q2) use ($familyId) {
                    $q2->where('target_type', 'KELUARGA')
                       ->where('family_id', $familyId);
                });
            });

            // Filter by status penyaluran
            if ($request->has('filter_status_penyaluran')) {
                $query->where('status', $request->filter_status_penyaluran);
            }

            // Filter by tahun (dari program)
            if ($request->has('filter_tahun')) {
                $query->whereHas('program', function($q) use ($request) {
                    $q->where('tahun', $request->filter_tahun);
                });
            }

            // Filter by status program
            if ($request->has('filter_status_program')) {
                $query->whereHas('program', function($q) use ($request) {
                    $q->where('status', $request->filter_status_program);
                });
            }

            // Search
            if ($request->has('search')) {
                $searchTerm = $request->search;
                $query->whereHas('program', function($q) use ($searchTerm) {
                    $q->where('nama_program', 'like', '%' . $searchTerm . '%')
                      ->orWhere('periode', 'like', '%' . $searchTerm . '%');
                });
            }

            // Sorting
            if ($request->has('sort')) {
                $order = $request->input('order', 'desc');
                $sortMapping = [
                    'tanggal_penyaluran' => 'tanggal_penyaluran',
                    'status' => 'status',
                    'created_at' => 'created_at',
                ];
                $sortColumn = $sortMapping[$request->sort] ?? 'created_at';
                $query->orderBy($sortColumn, $order);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Pagination
            $perPage = (int) $request->input('per_page', 10);
            
            if ($perPage === -1) {
                $allData = $query->get();
                $transformedData = $allData->map(function ($item) {
                    return $this->transformItemList($item);
                });

                return response()->json([
                    'success' => true,
                    'data' => $transformedData,
                    'meta' => [
                        'total' => $transformedData->count(),
                        'current_page' => 1,
                        'per_page' => -1,
                        'search' => $request->input('search', ''),
                        'sort' => $request->input('sort', ''),
                        'order' => $request->input('order', 'desc'),
                    ],
                ]);
            }

            $page = (int) $request->input('page', 0);
            $pageForLaravel = $page < 1 ? 1 : $page + 1;
            $result = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);

            $transformedData = $result->getCollection()->map(function ($item) {
                return $this->transformItemList($item);
            });

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'meta' => [
                    'total' => $result->total(),
                    'current_page' => $result->currentPage(),
                    'per_page' => $result->perPage(),
                    'search' => $request->input('search', ''),
                    'sort' => $request->input('sort', ''),
                    'order' => $request->input('order', 'desc'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data program bantuan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detail program bantuan riwayat saya
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

            // Auto-set resident_id dari user
            if (!$user->resident_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak memiliki data resident. Silakan lengkapi profil terlebih dahulu.',
                ], 400);
            }

            // Load resident dengan family
            $resident = $user->resident;
            if (!$resident) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data resident tidak ditemukan',
                ], 404);
            }

            $residentId = $resident->id;
            $familyId = $resident->family_id;

            // Get AssistanceRecipient dengan relasi
            $recipient = AssistanceRecipient::with([
                'program.program_items.item',
                'kepala_keluarga',
                'penerima_lapangan',
                'family',
                'resident.family'
            ])
            ->whereNull('assistance_recipients.deleted_at')
            ->where(function($q) use ($residentId, $familyId) {
                // Program INDIVIDU untuk user ini
                $q->where(function($q2) use ($residentId) {
                    $q2->where('target_type', 'INDIVIDU')
                       ->where('resident_id', $residentId);
                })
                // Program KELUARGA untuk keluarga user ini
                ->orWhere(function($q2) use ($familyId) {
                    $q2->where('target_type', 'KELUARGA')
                       ->where('family_id', $familyId);
                });
            })
            ->find($id);
            
            if (!$recipient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Program bantuan tidak ditemukan atau Anda tidak memiliki akses',
                ], 404);
            }

            $data = $this->transformItemDetail($recipient);
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data program bantuan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transform item untuk list
     */
    private function transformItemList($item)
    {
        // Get desil keluarga
        $desilKeluarga = null;
        if ($item->target_type === 'KELUARGA' && $item->family) {
            $desilKeluarga = $item->family->desil;
        } elseif ($item->target_type === 'INDIVIDU' && $item->resident && $item->resident->family) {
            $desilKeluarga = $item->resident->family->desil;
        }

        return [
            'id' => $item->id,
            'program_id' => $item->assistance_program_id,
            'nama_program' => $item->program->nama_program ?? '-',
            'tahun' => $item->program->tahun ?? null,
            'periode' => $item->program->periode ?? null,
            'status_program' => $item->program->status ?? null,
            'status_program_label' => $this->getProgramStatusLabel($item->program->status ?? null),
            'desil_program' => [
                'min' => $item->program->desil_min ?? null,
                'max' => $item->program->desil_max ?? null,
                'label' => $this->getDesilRangeLabel($item->program->desil_min ?? null, $item->program->desil_max ?? null),
            ],
            'jadwal_pengambilan' => [
                'tanggal' => $item->program->tanggal_penyaluran ? Carbon::parse($item->program->tanggal_penyaluran)->format('Y-m-d') : null,
                'jam_mulai' => $item->program->jam_mulai_pengambilan ?? null,
                'jam_selesai' => $item->program->jam_selesai_pengambilan ?? null,
                'label' => $this->getJadwalLabel($item->program),
            ],
            'desil_keluarga' => $desilKeluarga,
            'desil_keluarga_label' => $this->getDesilLabel($desilKeluarga),
            'target_type' => $item->target_type,
            'target_type_label' => $item->target_type === 'KELUARGA' ? 'Keluarga' : 'Individu',
            'status_penyaluran' => $item->status,
            'status_penyaluran_label' => $this->getStatusPenyaluranLabel($item->status),
            'tanggal_penyaluran' => $item->tanggal_penyaluran ? Carbon::parse($item->tanggal_penyaluran)->timezone('Asia/Jakarta')->format('Y-m-d') : null,
            'penerima_lapangan_nama' => $item->penerima_lapangan->nama ?? null,
            'penerima_lapangan_nik' => $item->penerima_lapangan->nik ?? null,
            'created_at' => $item->created_at ? Carbon::parse($item->created_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * Transform item untuk detail
     */
    private function transformItemDetail($item)
    {
        // Get program items
        $programItems = [];
        if ($item->program && $item->program->program_items) {
            foreach ($item->program->program_items as $programItem) {
                if ($programItem->item) {
                    $programItems[] = [
                        'id' => $programItem->id,
                        'nama_item' => $programItem->item->nama_item ?? '-',
                        'jumlah' => $programItem->jumlah,
                        'satuan' => $programItem->item->satuan ?? '-',
                        'tipe' => $programItem->item->tipe ?? 'BARANG',
                    ];
                }
            }
        }

        // Get kepala keluarga info
        $kepalaKeluarga = null;
        if ($item->kepala_keluarga) {
            $kepalaKeluarga = [
                'id' => $item->kepala_keluarga->id,
                'nama' => $item->kepala_keluarga->nama,
                'nik' => $item->kepala_keluarga->nik,
            ];
        }

        // Get penerima lapangan info
        $penerimaLapangan = null;
        if ($item->penerima_lapangan) {
            $penerimaLapangan = [
                'id' => $item->penerima_lapangan->id,
                'nama' => $item->penerima_lapangan->nama,
                'nik' => $item->penerima_lapangan->nik,
            ];
        }

        // Get family info (jika KELUARGA)
        $familyInfo = null;
        if ($item->target_type === 'KELUARGA' && $item->family) {
            $familyInfo = [
                'id' => $item->family->id,
                'no_kk' => $item->family->no_kk,
                'desil' => $item->family->desil,
                'desil_label' => $this->getDesilLabel($item->family->desil),
            ];
        }

        // Get resident info (jika INDIVIDU)
        $residentInfo = null;
        if ($item->target_type === 'INDIVIDU' && $item->resident) {
            $desilKeluargaResident = $item->resident->family ? $item->resident->family->desil : null;
            $residentInfo = [
                'id' => $item->resident->id,
                'nama' => $item->resident->nama,
                'nik' => $item->resident->nik,
                'desil_keluarga' => $desilKeluargaResident,
                'desil_keluarga_label' => $this->getDesilLabel($desilKeluargaResident),
            ];
        }

        return [
            'id' => $item->id,
            'program_id' => $item->assistance_program_id,
            'program' => [
                'id' => $item->program->id ?? null,
                'nama_program' => $item->program->nama_program ?? '-',
                'tahun' => $item->program->tahun ?? null,
                'periode' => $item->program->periode ?? null,
                'status' => $item->program->status ?? null,
                'status_label' => $this->getProgramStatusLabel($item->program->status ?? null),
                'target_penerima' => $item->program->target_penerima ?? null,
                'desil_min' => $item->program->desil_min ?? null,
                'desil_max' => $item->program->desil_max ?? null,
                'desil_label' => $this->getDesilRangeLabel($item->program->desil_min ?? null, $item->program->desil_max ?? null),
                'tanggal_penyaluran' => $item->program->tanggal_penyaluran ? Carbon::parse($item->program->tanggal_penyaluran)->format('Y-m-d') : null,
                'jam_mulai_pengambilan' => $item->program->jam_mulai_pengambilan ?? null,
                'jam_selesai_pengambilan' => $item->program->jam_selesai_pengambilan ?? null,
                'jadwal_label' => $this->getJadwalLabel($item->program),
                'keterangan' => $item->program->keterangan ?? null,
            ],
            'target_type' => $item->target_type,
            'target_type_label' => $item->target_type === 'KELUARGA' ? 'Keluarga' : 'Individu',
            'family' => $familyInfo,
            'resident' => $residentInfo,
            'kepala_keluarga' => $kepalaKeluarga,
            'penerima_lapangan' => $penerimaLapangan,
            'status_penyaluran' => $item->status,
            'status_penyaluran_label' => $this->getStatusPenyaluranLabel($item->status),
            'tanggal_penyaluran' => $item->tanggal_penyaluran ? Carbon::parse($item->tanggal_penyaluran)->timezone('Asia/Jakarta')->format('Y-m-d') : null,
            'catatan' => $item->catatan,
            'program_items' => $programItems,
            'created_at' => $item->created_at ? Carbon::parse($item->created_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : null,
            'updated_at' => $item->updated_at ? Carbon::parse($item->updated_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * Get status penyaluran label
     */
    private function getStatusPenyaluranLabel($status)
    {
        $labels = [
            'PROSES' => 'Proses',
            'DATANG' => 'Datang',
            'TIDAK_DATANG' => 'Tidak Datang',
        ];
        return $labels[$status] ?? $status;
    }

    /**
     * Get desil label berdasarkan nilai desil
     */
    private function getDesilLabel($desil)
    {
        if (!$desil) {
            return null;
        }

        $labels = [
            1 => 'Desil 1 - Sangat Miskin',
            2 => 'Desil 2 - Miskin',
            3 => 'Desil 3 - Hampir Miskin',
            4 => 'Desil 4 - Rentan Miskin',
            5 => 'Desil 5 - Pas-pasan',
            6 => 'Desil 6 - Menengah ke Atas',
            7 => 'Desil 7 - Menengah ke Atas',
            8 => 'Desil 8 - Menengah ke Atas',
            9 => 'Desil 9 - Menengah ke Atas',
            10 => 'Desil 10 - Menengah ke Atas',
        ];

        return $labels[$desil] ?? "Desil {$desil}";
    }

    /**
     * Get desil range label
     */
    private function getDesilRangeLabel($desilMin, $desilMax)
    {
        if (!$desilMin || !$desilMax) {
            return null;
        }

        if ($desilMin === $desilMax) {
            return $this->getDesilLabel($desilMin);
        }

        return "Desil {$desilMin} - {$desilMax}";
    }

    /**
     * Get program status label
     */
    private function getProgramStatusLabel($status)
    {
        $labels = [
            'PROSES' => 'Proses',
            'PENYALURAN' => 'Penyaluran',
            'SELESAI' => 'Selesai',
        ];
        return $labels[$status] ?? $status;
    }

    /**
     * Get jadwal label
     */
    private function getJadwalLabel($program)
    {
        if (!$program || !$program->tanggal_penyaluran) {
            return null;
        }
        
        $tanggal = Carbon::parse($program->tanggal_penyaluran)->format('d F Y');
        
        if ($program->jam_mulai_pengambilan && $program->jam_selesai_pengambilan) {
            $jamMulai = Carbon::parse($program->jam_mulai_pengambilan)->format('H:i');
            $jamSelesai = Carbon::parse($program->jam_selesai_pengambilan)->format('H:i');
            return "{$tanggal}, {$jamMulai} - {$jamSelesai} WIB";
        } elseif ($program->jam_mulai_pengambilan) {
            $jamMulai = Carbon::parse($program->jam_mulai_pengambilan)->format('H:i');
            return "{$tanggal}, mulai {$jamMulai} WIB";
        }
        
        return $tanggal;
    }

    /**
     * Validasi apakah masih dalam jadwal pengambilan
     */
    private function validateJadwalPengambilan($program)
    {
        if (!$program->tanggal_penyaluran) {
            return [
                'valid' => false,
                'message' => 'Program belum memiliki jadwal penyaluran',
            ];
        }

        $now = Carbon::now('Asia/Jakarta');
        $tanggalPenyaluran = Carbon::parse($program->tanggal_penyaluran, 'Asia/Jakarta');
        
        // Cek apakah sudah tanggal penyaluran
        if ($now->format('Y-m-d') < $tanggalPenyaluran->format('Y-m-d')) {
            return [
                'valid' => false,
                'message' => 'Jadwal pengambilan belum dimulai. Tanggal penyaluran: ' . $tanggalPenyaluran->format('d F Y'),
            ];
        }

        // Jika ada jam, validasi jam
        if ($program->jam_mulai_pengambilan && $program->jam_selesai_pengambilan) {
            $jamMulai = Carbon::parse($program->tanggal_penyaluran . ' ' . $program->jam_mulai_pengambilan, 'Asia/Jakarta');
            $jamSelesai = Carbon::parse($program->tanggal_penyaluran . ' ' . $program->jam_selesai_pengambilan, 'Asia/Jakarta');
            
            if ($now->lt($jamMulai)) {
                return [
                    'valid' => false,
                    'message' => 'Jadwal pengambilan belum dimulai. Jam mulai: ' . $jamMulai->format('H:i'),
                ];
            }
            
            if ($now->gt($jamSelesai)) {
                return [
                    'valid' => false,
                    'message' => 'Jadwal pengambilan sudah berakhir. Jam selesai: ' . $jamSelesai->format('H:i'),
                ];
            }
        } elseif ($program->jam_mulai_pengambilan) {
            // Hanya ada jam mulai
            $jamMulai = Carbon::parse($program->tanggal_penyaluran . ' ' . $program->jam_mulai_pengambilan, 'Asia/Jakarta');
            if ($now->lt($jamMulai)) {
                return [
                    'valid' => false,
                    'message' => 'Jadwal pengambilan belum dimulai. Jam mulai: ' . $jamMulai->format('H:i'),
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Absen mandiri - user absen sendiri dengan pilih perwakilan dan upload foto
     */
    public function absenMandiri(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi',
                ], 401);
            }

            if (!$user->resident_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak memiliki data resident',
                ], 400);
            }

            $resident = $user->resident;
            if (!$resident) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data resident tidak ditemukan',
                ], 404);
            }

            $residentId = $resident->id;
            $familyId = $resident->family_id;

            // Get AssistanceRecipient
            $recipient = AssistanceRecipient::with('program')
                ->whereNull('assistance_recipients.deleted_at')
                ->where(function($q) use ($residentId, $familyId) {
                    $q->where(function($q2) use ($residentId) {
                        $q2->where('target_type', 'INDIVIDU')
                           ->where('resident_id', $residentId);
                    })
                    ->orWhere(function($q2) use ($familyId) {
                        $q2->where('target_type', 'KELUARGA')
                           ->where('family_id', $familyId);
                    });
                })
                ->find($id);
                
            if (!$recipient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Program bantuan tidak ditemukan atau Anda tidak memiliki akses',
                ], 404);
            }

            // Validasi status - hanya yang PROSES bisa absen
            if ($recipient->status !== 'PROSES') {
                return response()->json([
                    'success' => false,
                    'message' => 'Status penyaluran sudah ' . $this->getStatusPenyaluranLabel($recipient->status) . '. Tidak bisa melakukan absen.',
                ], 400);
            }

            // Validasi jadwal
            $jadwalValidation = $this->validateJadwalPengambilan($recipient->program);
            if (!$jadwalValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $jadwalValidation['message'],
                ], 400);
            }

            // Handle multipart/form-data jika diperlukan
            if (str_contains($request->header('Content-Type', ''), 'multipart/form-data') || !empty($_FILES)) {
                // Merge $_POST data jika ada
                $postData = $_POST ?? [];
                foreach ($postData as $key => $value) {
                    if (!$request->has($key)) {
                        $request->merge([$key => $value]);
                    }
                }
                
                // Handle files dari $_FILES
                $filesData = $_FILES ?? [];
                if (!empty($filesData) && isset($filesData['foto_bukti'])) {
                    $fileData = $filesData['foto_bukti'];
                    if (isset($fileData['tmp_name']) && is_uploaded_file($fileData['tmp_name'])) {
                        $uploadedFile = \Illuminate\Http\UploadedFile::createFromBase(
                            new \Symfony\Component\HttpFoundation\File\UploadedFile(
                                $fileData['tmp_name'],
                                $fileData['name'],
                                $fileData['type'] ?? null,
                                $fileData['error'] ?? null,
                                true
                            )
                        );
                        $request->files->set('foto_bukti', $uploadedFile);
                    }
                }
            }

            // Validasi request - hanya foto bukti yang required
            try {
                $validated = $request->validate([
                    'foto_bukti' => 'required|image|mimes:jpeg,png,jpg|max:5120', // Max 5MB
                ], [
                    'foto_bukti.required' => 'Foto bukti wajib diupload',
                    'foto_bukti.image' => 'File harus berupa gambar',
                    'foto_bukti.mimes' => 'Format file harus JPG, PNG, atau JPEG',
                    'foto_bukti.max' => 'Ukuran file maksimal 5MB',
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $e->errors(),
                ], 422);
            }

            // Auto-set penerima_lapangan_id dari user yang login
            $penerimaLapanganId = $resident->id;

            // Upload foto bukti
            $fotoPath = null;
            if ($request->hasFile('foto_bukti')) {
                $foto = $request->file('foto_bukti');
                if ($foto && $foto->isValid()) {
                    try {
                        // Pastikan directory exists
                        $directory = 'assistance-recipients/' . $recipient->id;
                        $fotoPath = $foto->store($directory, 'public');
                    } catch (\Exception $e) {
                        \Log::error('Error upload foto bukti: ' . $e->getMessage());
                        return response()->json([
                            'success' => false,
                            'message' => 'Gagal mengupload foto: ' . $e->getMessage(),
                        ], 500);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'File foto tidak valid atau corrupt',
                    ], 422);
                }
            } else {
                // Debug info untuk troubleshooting
                $debugInfo = [
                    'has_file' => $request->hasFile('foto_bukti'),
                    'all_files' => array_keys($request->allFiles()),
                    'request_keys' => array_keys($request->all()),
                ];
                
                return response()->json([
                    'success' => false,
                    'message' => 'Foto bukti tidak ditemukan. Pastikan file terkirim dengan field name "foto_bukti" dan menggunakan FormData.',
                    'debug' => $debugInfo,
                ], 422);
            }

            // Update recipient
            $now = Carbon::now('Asia/Jakarta');
            $recipient->update([
                'penerima_lapangan_id' => $penerimaLapanganId, // Auto dari user yang login
                'foto_bukti_pengambilan' => $fotoPath,
                'status' => 'DATANG',
                'tanggal_penyaluran' => $now,
                'absen_mandiri' => true, // Flag bahwa ini absen mandiri
                'updated_by' => $user->id,
            ]);

            // Notifikasi ke admin
            $this->notifyAdminAbsenMandiri($recipient, $user, $resident);

            return response()->json([
                'success' => true,
                'message' => 'Absen berhasil dilakukan',
                'data' => $this->transformItemDetail($recipient->fresh()),
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
                'message' => 'Gagal melakukan absen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Notifikasi ke admin ketika user absen mandiri
     */
    private function notifyAdminAbsenMandiri($recipient, $user, $resident)
    {
        try {
            // Get semua admin (user dengan role admin atau permission Assistance Recipients)
            $admins = \App\Models\User::whereHas('users_role.role.permissions', function($q) {
                $q->where('permissions.name', 'like', '%Assistance Recipients%');
            })
            ->orWhereHas('users_role.role', function($q) {
                $q->where('roles.name', 'like', '%Admin%');
            })
            ->get();

            $program = $recipient->program;
            $targetLabel = $recipient->target_type === 'KELUARGA' 
                ? 'Keluarga: ' . ($recipient->family->no_kk ?? '-')
                : 'Individu: ' . ($recipient->resident->nama ?? '-');

            foreach ($admins as $admin) {
                \App\Models\Notification::create([
                    'notifiable_type' => \App\Models\User::class,
                    'notifiable_id' => $admin->id,
                    'data' => [
                        'type' => 'absen_mandiri',
                        'title' => 'Absen Mandiri Program Bantuan',
                        'message' => "User {$user->name} ({$resident->nama}) telah melakukan absen mandiri untuk program {$program->nama_program}. {$targetLabel}.",
                        'program_id' => $program->id,
                        'recipient_id' => $recipient->id,
                        'user_id' => $user->id,
                        'penerima_lapangan' => $resident->nama,
                        'tanggal' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    ],
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Gagal mengirim notifikasi absen mandiri: ' . $e->getMessage());
        }
    }

}

