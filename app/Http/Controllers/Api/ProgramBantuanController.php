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
                'resident'
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
                'resident'
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
        return [
            'id' => $item->id,
            'program_id' => $item->assistance_program_id,
            'nama_program' => $item->program->nama_program ?? '-',
            'tahun' => $item->program->tahun ?? null,
            'periode' => $item->program->periode ?? null,
            'status_program' => $item->program->status ?? null,
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
            ];
        }

        // Get resident info (jika INDIVIDU)
        $residentInfo = null;
        if ($item->target_type === 'INDIVIDU' && $item->resident) {
            $residentInfo = [
                'id' => $item->resident->id,
                'nama' => $item->resident->nama,
                'nik' => $item->resident->nik,
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
                'status_label' => $item->program->status === 'SELESAI' ? 'Selesai' : 'Proses',
                'target_penerima' => $item->program->target_penerima ?? null,
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
}

