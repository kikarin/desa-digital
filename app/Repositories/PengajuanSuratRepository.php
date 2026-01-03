<?php

namespace App\Repositories;

use App\Models\PengajuanSurat;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PengajuanSuratRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(PengajuanSurat $model)
    {
        $this->model = $model;
        $this->with = ['jenisSurat', 'resident', 'adminVerifikasi', 'created_by_user', 'updated_by_user'];
    }

    public function customIndex($data)
    {
        $query = $this->model->with(['jenisSurat', 'resident']);

        // Filter by created_by (for warga to see their own pengajuan)
        // Check both request and data array
        $filterCreatedBy = $data['filter_created_by'] ?? request('filter_created_by');
        if ($filterCreatedBy) {
            $query->where('created_by', $filterCreatedBy);
        }

        // Filter by resident_id (for admin filtering)
        $filterResidentId = $data['filter_resident_id'] ?? request('filter_resident_id');
        if ($filterResidentId) {
            $query->where('resident_id', $filterResidentId);
        }

        // Filter by status
        if (request('filter_status')) {
            $query->where('status', request('filter_status'));
        }

        // Filter by jenis_surat_id
        if (request('filter_jenis_surat_id')) {
            $query->where('jenis_surat_id', request('filter_jenis_surat_id'));
        }

        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nomor_surat', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('jenisSurat', function ($q) use ($searchTerm) {
                        $q->where('nama', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('resident', function ($q) use ($searchTerm) {
                        $q->where('nama', 'like', '%' . $searchTerm . '%')
                            ->orWhere('nik', 'like', '%' . $searchTerm . '%');
                    });
            });
        }

        if (request('sort')) {
            $order = request('order', 'desc');
            $sortMapping = [
                'tanggal_surat' => 'tanggal_surat',
                'status' => 'status',
                'nomor_surat' => 'nomor_surat',
            ];

            $sortColumn = $sortMapping[request('sort')] ?? 'created_at';
            $query->orderBy($sortColumn, $order);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = (int) request('per_page', 10);
        
        if ($perPage === -1) {
            $allData = $query->get();
            $transformedData = $allData->map(function ($item) {
                return $this->transformPengajuanData($item);
            });

            $data += [
                'pengajuan_surat' => $transformedData,
                'meta' => [
                    'total' => $transformedData->count(),
                    'current_page' => 1,
                    'per_page' => -1,
                    'search' => request('search', ''),
                    'sort' => request('sort', ''),
                    'order' => request('order', 'desc'),
                ],
            ];
            return $data;
        }

        $page = (int) request('page', 0);
        $pageForLaravel = $page < 1 ? 1 : $page + 1;
        $result = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);

        $transformedData = $result->getCollection()->map(function ($item) {
            return $this->transformPengajuanData($item);
        });

        $data += [
            'pengajuan_surat' => $transformedData,
            'meta' => [
                'total' => $result->total(),
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'search' => request('search', ''),
                'sort' => request('sort', ''),
                'order' => request('order', 'desc'),
            ],
        ];

        return $data;
    }

    private function transformPengajuanData($item)
    {
        return [
            'id' => $item->id,
            'jenis_surat_id' => $item->jenis_surat_id,
            'jenis_surat_nama' => $item->jenisSurat->nama ?? '-',
            'jenis_surat_kode' => $item->jenisSurat->kode ?? '-',
            'resident_id' => $item->resident_id,
            'resident_nama' => $item->resident->nama ?? '-',
            'resident_nik' => $item->resident->nik ?? '-',
            'tanggal_surat' => $item->tanggal_surat ? Carbon::parse($item->tanggal_surat)->timezone('Asia/Jakarta')->format('Y-m-d') : null,
            'status' => $item->status,
            'nomor_surat' => $item->nomor_surat,
            'tanggal_disetujui' => $item->tanggal_disetujui ? Carbon::parse($item->tanggal_disetujui)->timezone('Asia/Jakarta')->format('Y-m-d') : null,
            'alasan_penolakan' => $item->alasan_penolakan,
            'admin_verifikasi_id' => $item->admin_verifikasi_id,
            'tanda_tangan_digital' => $item->tanda_tangan_digital,
            'foto_tanda_tangan' => $item->foto_tanda_tangan,
            'tanda_tangan_type' => $item->tanda_tangan_type,
            'can_be_edited' => $item->canBeEdited(),
            'created_at' => $item->created_at ? Carbon::parse($item->created_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : null,
            'updated_at' => $item->updated_at ? Carbon::parse($item->updated_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : null,
            'created_by_user' => $item->created_by_user ? [
                'id' => $item->created_by_user->id,
                'name' => $item->created_by_user->name,
            ] : null,
            'updated_by_user' => $item->updated_by_user ? [
                'id' => $item->updated_by_user->id,
                'name' => $item->updated_by_user->name,
            ] : null,
        ];
    }

    public function customCreateEdit($data, $item = null)
    {
        // Get jenis surat options for dropdown
        $data['jenis_surat_options'] = \App\Models\JenisSurat::orderBy('nama', 'asc')
            ->pluck('nama', 'id')
            ->toArray();
        
        // Get residents options for dropdown (if needed)
        $data['resident_options'] = \App\Models\Residents::orderBy('nama', 'asc')
            ->select('id', 'nama', 'nik')
            ->get()
            ->mapWithKeys(function ($resident) {
                return [$resident->id => $resident->nama . ' - ' . $resident->nik];
            })
            ->toArray();

        if ($item) {
            // Load atribut for editing
            $atributData = [];
            foreach ($item->atribut as $atribut) {
                $lampiranFiles = [];
                if ($atribut->lampiran_files) {
                    // Handle both array and JSON string
                    if (is_string($atribut->lampiran_files)) {
                        $decoded = json_decode($atribut->lampiran_files, true);
                        $lampiranFiles = is_array($decoded) ? $decoded : [];
                    } else {
                        $lampiranFiles = is_array($atribut->lampiran_files) ? $atribut->lampiran_files : [];
                    }
                }
                
                $atributData[$atribut->atribut_jenis_surat_id] = [
                    'nilai' => $atribut->nilai ?? '',
                    'lampiran_files' => $lampiranFiles,
                    'existing_files' => $lampiranFiles, // Store existing files for display
                ];
            }
            $data['atribut'] = $atributData;
            
            // Also add item data for form
            $data['item'] = $this->transformPengajuanData($item);
        }

        return $data;
    }

    public function customShow($data, $item = null)
    {
        if ($item) {
            // Transform item data
            $data['item'] = $this->transformPengajuanData($item);
            
            // Load atribut with relationship
            $item->load('atribut.atributJenisSurat');
            
            // Add atribut detail
            $atributDetail = [];
            foreach ($item->atribut as $atribut) {
                // Handle lampiran_files (can be array or JSON string)
                $lampiranFiles = [];
                if ($atribut->lampiran_files) {
                    if (is_string($atribut->lampiran_files)) {
                        $decoded = json_decode($atribut->lampiran_files, true);
                        $lampiranFiles = is_array($decoded) ? $decoded : [];
                    } else {
                        $lampiranFiles = is_array($atribut->lampiran_files) ? $atribut->lampiran_files : [];
                    }
                }
                
                $atributDetail[] = [
                    'id' => $atribut->id,
                    'atribut_jenis_surat_id' => $atribut->atribut_jenis_surat_id,
                    'atribut_nama' => $atribut->atributJenisSurat->nama_atribut ?? '-',
                    'atribut_tipe' => $atribut->atributJenisSurat->tipe_data ?? '-',
                    'nilai' => $atribut->nilai ?? '-',
                    'lampiran_files' => $lampiranFiles,
                ];
            }
            
            $data['atribut_detail'] = $atributDetail;
        }
        return $data;
    }

    /**
     * Generate nomor surat
     */
    public function generateNomorSurat($pengajuanSurat)
    {
        $jenisSurat = $pengajuanSurat->jenisSurat;
        $kodeJenisSurat = $jenisSurat->kode;
        $singkatanInstansi = config('desa.singkatan_instansi', 'PEMDES-GALUGA');
        
        // Get nomor urut untuk tahun ini
        $tahun = date('Y');
        $bulan = date('n'); // 1-12
        $bulanRomawi = $this->convertToRoman($bulan);
        
        // Get last nomor urut for this jenis surat in this year
        $lastPengajuan = $this->model
            ->where('jenis_surat_id', $jenisSurat->id)
            ->whereYear('tanggal_disetujui', $tahun)
            ->whereNotNull('nomor_surat')
            ->orderBy('id', 'desc')
            ->first();
        
        $nomorUrut = 1;
        if ($lastPengajuan && $lastPengajuan->nomor_surat) {
            // Extract nomor urut from last nomor surat
            $parts = explode('/', $lastPengajuan->nomor_surat);
            if (isset($parts[0]) && is_numeric($parts[0])) {
                $nomorUrut = (int) $parts[0] + 1;
            }
        }
        
        // Format: [Nomor Urut]/[Kode Jenis Surat]/[Singkatan Instansi]/[Bulan Romawi]/[Tahun]
        $nomorSurat = sprintf(
            '%02d/%s/%s/%s/%s',
            $nomorUrut,
            $kodeJenisSurat,
            $singkatanInstansi,
            $bulanRomawi,
            $tahun
        );
        
        return $nomorSurat;
    }

    /**
     * Convert number to Roman numeral
     */
    private function convertToRoman($number)
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V',
            6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X',
            11 => 'XI', 12 => 'XII'
        ];
        
        return $map[$number] ?? 'I';
    }
}

