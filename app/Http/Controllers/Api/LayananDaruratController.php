<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\LayananDaruratRepository;
use Illuminate\Http\Request;

class LayananDaruratController extends Controller
{
    protected $repository;

    public function __construct(LayananDaruratRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get list layanan darurat
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $data = $this->repository->customIndex([]);
            
            return response()->json([
                'success' => true,
                'data' => $data['layanan_darurat'] ?? [],
                'meta' => $data['meta'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data layanan darurat',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list kategori layanan darurat
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKategori()
    {
        try {
            $kategoris = [
                [
                    'value' => 'polsek',
                    'label' => 'Polsek',
                ],
                [
                    'value' => 'puskesmas',
                    'label' => 'Puskesmas',
                ],
                [
                    'value' => 'rumah_sakit',
                    'label' => 'RS',
                ],
                [
                    'value' => 'pemadam_kebakaran',
                    'label' => 'Damkar',
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $kategoris,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kategori',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

