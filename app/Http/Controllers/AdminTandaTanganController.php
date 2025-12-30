<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminTandaTanganRequest;
use App\Repositories\AdminTandaTanganRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminTandaTanganController extends Controller
{
    private $repository;

    public function __construct(AdminTandaTanganRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all TTD for current admin
     */
    public function index()
    {
        $adminId = Auth::id();
        $ttdList = $this->repository->getAllTtdByAdmin($adminId);

        return response()->json([
            'success' => true,
            'data' => $ttdList->map(function ($ttd) {
                return [
                    'id' => $ttd->id,
                    'tanda_tangan_type' => $ttd->tanda_tangan_type,
                    'has_digital' => !empty($ttd->tanda_tangan_digital),
                    'has_foto' => !empty($ttd->foto_tanda_tangan),
                ];
            }),
        ]);
    }

    /**
     * Store or update TTD
     */
    public function store(AdminTandaTanganRequest $request)
    {
        try {
            $adminId = Auth::id();
            $type = $request->tanda_tangan_type;

            $ttd = $this->repository->getOrCreateTtd($adminId, $type);

            if ($type === 'digital') {
                $ttd->update([
                    'tanda_tangan_digital' => $request->tanda_tangan_digital,
                ]);
            } else {
                if ($request->hasFile('foto_tanda_tangan')) {
                    // Delete old file if exists
                    if ($ttd->foto_tanda_tangan && Storage::disk('public')->exists($ttd->foto_tanda_tangan)) {
                        Storage::disk('public')->delete($ttd->foto_tanda_tangan);
                    }

                    $file = $request->file('foto_tanda_tangan');
                    $path = $file->store('admin-tanda-tangan', 'public');
                    $ttd->update([
                        'foto_tanda_tangan' => $path,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Tanda tangan berhasil disimpan.',
                'data' => $ttd,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan tanda tangan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get TTD by type
     */
    public function getByType($type)
    {
        $adminId = Auth::id();
        $ttd = $this->repository->getTtdByAdminAndType($adminId, $type);

        if (!$ttd) {
            return response()->json([
                'success' => false,
                'message' => 'Tanda tangan tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $ttd->id,
                'tanda_tangan_type' => $ttd->tanda_tangan_type,
                'tanda_tangan_digital' => $ttd->tanda_tangan_digital,
                'foto_tanda_tangan' => $ttd->foto_tanda_tangan ? Storage::url($ttd->foto_tanda_tangan) : null,
            ],
        ]);
    }
}

