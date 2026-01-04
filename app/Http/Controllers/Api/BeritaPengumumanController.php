<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\BeritaPengumumanRepository;
use App\Models\BeritaPengumuman;
use Illuminate\Http\Request;

class BeritaPengumumanController extends Controller
{
    protected $repository;

    public function __construct(BeritaPengumumanRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get navbar berita pengumuman
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNavbar()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'nama' => 'Berita & Pengumuman',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data navbar',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list tipe berita pengumuman (untuk filter)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTipe()
    {
        try {
            $tipes = [
                [
                    'value' => 'berita',
                    'label' => 'Berita',
                ],
                [
                    'value' => 'event',
                    'label' => 'Pengumuman',
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $tipes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data tipe',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list berita pengumuman
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Gunakan query parameter 'tipe' untuk filter
            if ($request->has('tipe') && $request->tipe) {
                $request->merge(['filter_tipe' => $request->tipe]);
            }

            $data = $this->repository->customIndex([]);
            
            // Transform data untuk PWA
            $transformedData = collect($data['berita_pengumuman'] ?? [])->map(function ($item) {
                return $this->transformItemList($item);
            });

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'meta' => $data['meta'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data berita pengumuman',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detail berita pengumuman
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $item = BeritaPengumuman::find($id);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Berita pengumuman tidak ditemukan',
                ], 404);
            }

            $data = $this->repository->customShow([], $item);
            $transformedData = $this->transformItemDetail($data['item'] ?? []);

            return response()->json([
                'success' => true,
                'data' => $transformedData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data detail berita pengumuman',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transform item untuk list
     */
    private function transformItemList($item)
    {
        // Handle both array and object
        $id = is_array($item) ? ($item['id'] ?? null) : ($item->id ?? null);
        $tipe = is_array($item) ? ($item['tipe'] ?? null) : ($item->tipe ?? null);
        $title = is_array($item) ? ($item['title'] ?? null) : ($item->title ?? null);
        $foto = is_array($item) ? ($item['foto'] ?? null) : ($item->foto ? asset('storage/' . $item->foto) : null);
        $tanggal = is_array($item) ? ($item['tanggal'] ?? null) : (isset($item->tanggal) ? $item->tanggal->format('Y-m-d') : null);
        $deskripsi = is_array($item) ? ($item['deskripsi'] ?? null) : ($item->deskripsi ?? null);

        return [
            'id' => $id,
            'tipe' => $tipe,
            'tipe_label' => $tipe === 'berita' ? 'Berita' : 'Pengumuman',
            'title' => $title,
            'foto' => $foto,
            'tanggal' => $tanggal,
            'deskripsi' => $deskripsi,
        ];
    }

    /**
     * Transform item untuk detail
     */
    private function transformItemDetail($item)
    {
        return [
            'id' => $item['id'] ?? null,
            'tipe' => $item['tipe'] ?? null,
            'tipe_label' => ($item['tipe'] ?? '') === 'berita' ? 'Berita' : 'Pengumuman',
            'title' => $item['title'] ?? null,
            'foto' => $item['foto'] ?? null,
            'tanggal' => $item['tanggal'] ?? null,
            'deskripsi' => $item['deskripsi'] ?? null,
            'created_at' => $item['created_at'] ?? null,
            'updated_at' => $item['updated_at'] ?? null,
        ];
    }
}

