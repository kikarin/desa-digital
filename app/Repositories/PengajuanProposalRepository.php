<?php

namespace App\Repositories;

use App\Models\PengajuanProposal;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PengajuanProposalRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(PengajuanProposal $model)
    {
        $this->model = $model;
        $this->with = ['kategoriProposal', 'resident', 'created_by_user', 'updated_by_user', 'adminVerifikasi'];
    }

    public function customIndex($data, $isPengajuanSaya = false)
    {
        $query = $this->model->select(
            'id',
            'kategori_proposal_id',
            'resident_id',
            'nama_kegiatan',
            'usulan_anggaran',
            'status',
            'latitude',
            'longitude',
            'created_at'
        );

        // Filter untuk pengajuan saya (hanya milik user yang login)
        if ($isPengajuanSaya) {
            $user = Auth::user();
            if ($user && $user->resident_id) {
                $query->where('resident_id', $user->resident_id);
            } else {
                // Jika user tidak punya resident_id, filter berdasarkan created_by
                $query->where('created_by', $user->id);
            }
        }

        // Search
        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nama_kegiatan', 'like', '%' . $searchTerm . '%')
                    ->orWhere('deskripsi_kegiatan', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('resident', function ($q) use ($searchTerm) {
                        $q->where('nama', 'like', '%' . $searchTerm . '%')
                            ->orWhere('nik', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('kategoriProposal', function ($q) use ($searchTerm) {
                        $q->where('nama', 'like', '%' . $searchTerm . '%');
                    });
            });
        }

        // Filter by status
        if (request('status')) {
            $query->where('status', request('status'));
        }

        // Filter by kategori
        if (request('kategori_proposal_id')) {
            $query->where('kategori_proposal_id', request('kategori_proposal_id'));
        }

        // Sorting
        if (request('sort')) {
            $order = request('order', 'asc');
            $sortMapping = [
                'nama_kegiatan' => 'nama_kegiatan',
                'usulan_anggaran' => 'usulan_anggaran',
                'status' => 'status',
                'created_at' => 'created_at',
            ];
            $sortColumn = $sortMapping[request('sort')] ?? 'id';
            $query->orderBy($sortColumn, $order);
        } else {
            $query->orderBy('id', 'desc');
        }

        // Pagination
        $perPage = (int) request('per_page', 10);
        
        if ($perPage === -1) {
            $allData = $query->get();
            $transformedData = $allData->map(function ($item) {
                return $this->transformItem($item);
            });

            $data += [
                'pengajuan_proposal' => $transformedData,
                'meta' => [
                    'total' => $transformedData->count(),
                    'current_page' => 1,
                    'per_page' => -1,
                    'search' => request('search', ''),
                    'sort' => request('sort', ''),
                    'order' => request('order', 'asc'),
                ],
            ];
            return $data;
        }

        $page = (int) request('page', 0);
        $pageForLaravel = $page < 1 ? 1 : $page + 1;
        $items = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);

        $transformedData = $items->getCollection()->map(function ($item) {
            return $this->transformItem($item);
        });

        $data += [
            'pengajuan_proposal' => $transformedData,
            'meta' => [
                'total' => $items->total(),
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'search' => request('search', ''),
                'sort' => request('sort', ''),
                'order' => request('order', 'asc'),
            ],
        ];

        return $data;
    }

    private function transformItem($item)
    {
        return [
            'id' => $item->id,
            'kategori_proposal_id' => $item->kategori_proposal_id,
            'kategori_proposal_nama' => $item->kategoriProposal->nama ?? '-',
            'resident_id' => $item->resident_id,
            'resident_nama' => $item->resident->nama ?? '-',
            'resident_nik' => $item->resident->nik ?? '-',
            'nama_kegiatan' => $item->nama_kegiatan,
            'usulan_anggaran' => $item->usulan_anggaran,
            'usulan_anggaran_formatted' => 'Rp ' . number_format($item->usulan_anggaran, 0, ',', '.'),
            'status' => $item->status,
            'status_label' => $item->status_label,
            'latitude' => $item->latitude,
            'longitude' => $item->longitude,
            'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function customCreateEdit($data, $item = null)
    {
        if ($item) {
            $data['item'] = [
                'id' => $item->id,
                'kategori_proposal_id' => $item->kategori_proposal_id,
                'resident_id' => $item->resident_id,
                'nama_kegiatan' => $item->nama_kegiatan,
                'deskripsi_kegiatan' => $item->deskripsi_kegiatan,
                'usulan_anggaran' => $item->usulan_anggaran,
                'file_pendukung' => $item->file_pendukung ?? [],
                'latitude' => $item->latitude,
                'longitude' => $item->longitude,
                'kecamatan_id' => $item->kecamatan_id,
                'desa_id' => $item->desa_id,
                'kecamatan' => $item->kecamatan,
                'kelurahan_desa' => $item->kelurahan_desa,
                'deskripsi_lokasi_tambahan' => $item->deskripsi_lokasi_tambahan,
                'thumbnail_foto_banner' => $item->thumbnail_foto_banner,
                'tanda_tangan_digital' => $item->tanda_tangan_digital,
                'status' => $item->status,
            ];
        }
        return $data;
    }

    public function customShow($data, $item = null)
    {
        if ($item) {
            $data['item'] = [
                'id' => $item->id,
                'kategori_proposal_id' => $item->kategori_proposal_id,
                'kategori_proposal_nama' => $item->kategoriProposal->nama ?? '-',
                'resident_id' => $item->resident_id,
                'resident_nama' => $item->resident->nama ?? '-',
                'resident_nik' => $item->resident->nik ?? '-',
                'nama_kegiatan' => $item->nama_kegiatan,
                'deskripsi_kegiatan' => $item->deskripsi_kegiatan,
                'usulan_anggaran' => $item->usulan_anggaran,
                'usulan_anggaran_formatted' => 'Rp ' . number_format($item->usulan_anggaran, 0, ',', '.'),
                'file_pendukung' => $item->file_pendukung ?? [],
                'latitude' => $item->latitude,
                'longitude' => $item->longitude,
                'kecamatan_id' => $item->kecamatan_id,
                'desa_id' => $item->desa_id,
                'kecamatan' => $item->kecamatan,
                'kelurahan_desa' => $item->kelurahan_desa,
                'deskripsi_lokasi_tambahan' => $item->deskripsi_lokasi_tambahan,
                'thumbnail_foto_banner' => $item->thumbnail_foto_banner,
                'tanda_tangan_digital' => $item->tanda_tangan_digital,
                'status' => $item->status,
                'status_label' => $item->status_label,
                'catatan_verifikasi' => $item->catatan_verifikasi,
                'admin_verifikasi_nama' => $item->adminVerifikasi->name ?? '-',
                'tanggal_diverifikasi' => $item->tanggal_diverifikasi?->format('Y-m-d H:i:s'),
                'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $item->updated_at?->format('Y-m-d H:i:s'),
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
        return $data;
    }

    public function customDataCreateUpdate($data, $record = null)
    {
        // Set status default jika create
        if (!$record) {
            $data['status'] = 'menunggu_verifikasi';
        }

        // Handle file uploads - file_pendukung
        $filePaths = [];
        
        // Handle existing files first (keep them)
        if (isset($data['existing_files']) && is_array($data['existing_files'])) {
            foreach ($data['existing_files'] as $file) {
                if (is_string($file) && !empty($file)) {
                    $filePaths[] = $file;
                }
            }
        } elseif ($record && $record->file_pendukung) {
            // If no existing_files in request but record has files, keep them
            $existingFiles = is_array($record->file_pendukung) ? $record->file_pendukung : json_decode($record->file_pendukung, true) ?? [];
            $filePaths = array_merge($filePaths, $existingFiles);
        }
        
        // Handle new files from request (array index format: file_pendukung[0], file_pendukung[1], etc)
        // Laravel will parse these into array
        if (isset($data['file_pendukung']) && is_array($data['file_pendukung'])) {
            foreach ($data['file_pendukung'] as $file) {
                if ($file && is_object($file) && method_exists($file, 'isValid') && $file->isValid()) {
                    $path = $file->store('pengajuan-proposal/files', 'public');
                    $filePaths[] = $path;
                } elseif (is_string($file)) {
                    // If it's already a path string, keep it
                    $filePaths[] = $file;
                }
            }
        }
        
        $data['file_pendukung'] = !empty($filePaths) ? $filePaths : null;
        
        // Remove existing_files from data (not needed in database)
        unset($data['existing_files']);

        // Handle thumbnail upload
        if (isset($data['thumbnail_foto_banner']) && is_object($data['thumbnail_foto_banner']) && method_exists($data['thumbnail_foto_banner'], 'isValid') && $data['thumbnail_foto_banner']->isValid()) {
            // Hapus file lama jika ada
            if ($record && $record->thumbnail_foto_banner) {
                Storage::disk('public')->delete($record->thumbnail_foto_banner);
            }
            $data['thumbnail_foto_banner'] = $data['thumbnail_foto_banner']->store('pengajuan-proposal/thumbnails', 'public');
        } elseif ($record && !isset($data['thumbnail_foto_banner'])) {
            // Keep existing thumbnail if not updating
            $data['thumbnail_foto_banner'] = $record->thumbnail_foto_banner;
        }

        return $data;
    }
}

