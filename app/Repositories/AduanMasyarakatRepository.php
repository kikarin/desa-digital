<?php

namespace App\Repositories;

use App\Models\AduanMasyarakat;
use App\Models\AduanMasyarakatFile;
use App\Traits\RepositoryTrait;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AduanMasyarakatRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(AduanMasyarakat $model)
    {
        $this->model = $model;
        $this->with = ['kategori_aduan', 'kecamatan', 'desa', 'created_by_user', 'updated_by_user', 'files'];
    }

    public function customIndex($data, $filterByCreatedBy = false)
    {
        // Load relasi dengan null handling (withDefault sudah di-set di model)
        $query = $this->model->with(['kategori_aduan', 'kecamatan', 'desa', 'created_by_user', 'updated_by_user', 'files'])
            ->select('id', 'kategori_aduan_id', 'judul', 'detail_aduan', 'latitude', 'longitude', 'nama_lokasi', 'kecamatan_id', 'desa_id', 'deskripsi_lokasi', 'jenis_aduan', 'alasan_melaporkan', 'status', 'created_by', 'created_at');

        // Filter by created_by jika untuk "Aduan Saya"
        if ($filterByCreatedBy && auth()->check()) {
            $query->where('created_by', auth()->id());
        }

        // Search
        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('judul', 'like', '%' . $searchTerm . '%')
                    ->orWhere('detail_aduan', 'like', '%' . $searchTerm . '%')
                    ->orWhere('nama_lokasi', 'like', '%' . $searchTerm . '%')
                    ->orWhere('deskripsi_lokasi', 'like', '%' . $searchTerm . '%')
                    ->orWhere('alasan_melaporkan', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filter by status
        if (request('status')) {
            $query->where('status', request('status'));
        }

        // Filter by jenis_aduan
        if (request('jenis_aduan')) {
            $query->where('jenis_aduan', request('jenis_aduan'));
        }

        // Sorting
        if (request('sort')) {
            $order = request('order', 'asc');
            $sortMapping = [
                'judul' => 'judul',
                'status' => 'status',
                'jenis_aduan' => 'jenis_aduan',
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
                return $this->transformItemList($item);
            });

            $data += [
                'aduan_masyarakat' => $transformedData,
                'meta' => [
                    'total'        => $transformedData->count(),
                    'current_page' => 1,
                    'per_page'     => -1,
                    'search'       => request('search', ''),
                    'sort'         => request('sort', ''),
                    'order'        => request('order', 'asc'),
                ],
            ];
            return $data;
        }

        $page           = (int) request('page', 0);
        $pageForLaravel = $page < 1 ? 1 : $page + 1;
        $items          = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);

        $transformedData = $items->getCollection()->map(function ($item) {
            return $this->transformItemList($item);
        });

        $data += [
            'aduan_masyarakat' => $transformedData,
            'meta' => [
                'total'        => $items->total(),
                'current_page' => $items->currentPage(),
                'per_page'     => $items->perPage(),
                'search'       => request('search', ''),
                'sort'         => request('sort', ''),
                'order'        => request('order', 'asc'),
            ],
        ];

        return $data;
    }

    /**
     * Transform item untuk list (ringkas)
     */
    private function transformItemList($item)
    {
        // Ambil foto pertama saja (jika ada)
        $foto = null;
        if ($item->files && $item->files->count() > 0) {
            $firstFile = $item->files->first();
            if ($firstFile && $firstFile->file_type === 'foto') {
                $foto = asset('storage/' . $firstFile->file_path);
            }
        }

        return [
            'id'                => $item->id,
            'judul'             => $item->judul,
            'tanggal'           => $item->created_at ? Carbon::parse($item->created_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : '-',
            'foto'              => $foto,
            'kategori_aduan_nama' => $item->kategori_aduan?->nama ?? '-',
            // Tambahkan field yang diperlukan untuk map
            'latitude'          => $item->latitude,
            'longitude'         => $item->longitude,
            'status'            => $item->status,
            'jenis_aduan'       => $item->jenis_aduan,
            'nama_lokasi'       => $item->nama_lokasi,
            'kecamatan_nama'    => $item->kecamatan?->nama ?? '-',
            'desa_nama'         => $item->desa?->nama ?? '-',
            'deskripsi_lokasi'  => $item->deskripsi_lokasi,
            'detail_aduan'      => $item->detail_aduan,
            'alasan_melaporkan' => $item->alasan_melaporkan,
        ];
    }

    /**
     * Transform item untuk detail (lengkap)
     */
    private function transformItem($item)
    {
        return [
            'id'                => $item->id,
            'kategori_aduan_id' => $item->kategori_aduan_id,
            'kategori_aduan_nama' => $item->kategori_aduan?->nama ?? '-',
            'judul'             => $item->judul,
            'detail_aduan'      => $item->detail_aduan,
            'latitude'          => $item->latitude,
            'longitude'         => $item->longitude,
            'nama_lokasi'       => $item->nama_lokasi,
            'kecamatan_id'      => $item->kecamatan_id,
            'kecamatan_nama'    => $item->kecamatan?->nama ?? '-',
            'desa_id'           => $item->desa_id,
            'desa_nama'         => $item->desa?->nama ?? '-',
            'deskripsi_lokasi'  => $item->deskripsi_lokasi,
            'jenis_aduan'       => $item->jenis_aduan,
            'alasan_melaporkan' => $item->alasan_melaporkan,
            'status'            => $item->status,
            'files'             => $item->files && $item->files->count() > 0 ? $item->files->map(function ($file) {
                return [
                    'id'        => $file->id,
                    'file_path' => asset('storage/' . $file->file_path),
                    'file_type' => $file->file_type,
                    'file_name' => $file->file_name,
                ];
            }) : [],
            'created_by_user'   => $item->created_by_user ? [
                'id'   => $item->created_by_user->id,
                'name' => $item->created_by_user->name,
            ] : null,
        ];
    }

    public function customCreateEdit($data, $item = null)
    {
        if ($item) {
            $data['item'] = [
                'id'                => $item->id,
                'kategori_aduan_id' => $item->kategori_aduan_id,
                'judul'             => $item->judul,
                'detail_aduan'      => $item->detail_aduan,
                'latitude'          => $item->latitude,
                'longitude'         => $item->longitude,
                'nama_lokasi'       => $item->nama_lokasi,
                'kecamatan_id'      => $item->kecamatan_id,
                'desa_id'           => $item->desa_id,
                'deskripsi_lokasi'  => $item->deskripsi_lokasi,
                'jenis_aduan'       => $item->jenis_aduan,
                'alasan_melaporkan' => $item->alasan_melaporkan,
                'status'            => $item->status,
                'files'             => $item->files && $item->files->count() > 0 ? $item->files->map(function ($file) {
                    return [
                        'id'        => $file->id,
                        'file_path' => asset('storage/' . $file->file_path),
                        'file_type' => $file->file_type,
                        'file_name' => $file->file_name,
                    ];
                }) : [],
            ];
        }
        return $data;
    }

    public function customShow($data, $item = null)
    {
        if ($item) {
            $data['item'] = [
                'id'                => $item->id,
                'kategori_aduan_id' => $item->kategori_aduan_id,
                'kategori_aduan_nama' => $item->kategori_aduan?->nama ?? '-',
                'judul'             => $item->judul,
                'detail_aduan'      => $item->detail_aduan,
                'latitude'          => $item->latitude,
                'longitude'         => $item->longitude,
                'nama_lokasi'       => $item->nama_lokasi,
                'kecamatan_id'      => $item->kecamatan_id,
                'kecamatan_nama'    => $item->kecamatan?->nama ?? '-',
                'desa_id'           => $item->desa_id,
                'desa_nama'         => $item->desa?->nama ?? '-',
                'deskripsi_lokasi'  => $item->deskripsi_lokasi,
                'jenis_aduan'       => $item->jenis_aduan,
                'alasan_melaporkan' => $item->alasan_melaporkan,
                'status'            => $item->status,
                'files'             => $item->files && $item->files->count() > 0 ? $item->files->map(function ($file) {
                    return [
                        'id'        => $file->id,
                        'file_path' => asset('storage/' . $file->file_path),
                        'file_type' => $file->file_type,
                        'file_name' => $file->file_name,
                    ];
                })->values()->toArray() : [],
                'created_at'        => $item->created_at ? Carbon::parse($item->created_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : null,
                'updated_at'        => $item->updated_at ? Carbon::parse($item->updated_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : null,
                'created_by_user'   => $item->created_by_user ? [
                    'id'   => $item->created_by_user->id,
                    'name' => $item->created_by_user->name,
                ] : null,
                'updated_by_user'   => $item->updated_by_user ? [
                    'id'   => $item->updated_by_user->id,
                    'name' => $item->updated_by_user->name,
                ] : null,
            ];
        }
        return $data;
    }

    public function customGetById($query)
    {
        // Pastikan relasi di-load dengan null handling
        return $query->with([
            'kategori_aduan' => function($q) {
                // Tidak perlu constraint tambahan, withDefault sudah di model
            },
            'kecamatan' => function($q) {
                // Tidak perlu constraint tambahan, withDefault sudah di model
            },
            'desa' => function($q) {
                // Tidak perlu constraint tambahan, withDefault sudah di model
            },
            'created_by_user',
            'updated_by_user',
            'files'
        ]);
    }

    public function customDataCreateUpdate($data, $record = null)
    {
        // Handle multiple files upload - files akan di-handle di Controller setelah record dibuat
        // karena kita perlu record->id terlebih dahulu
        $filesToStore = [];
        if (isset($data['files']) && is_array($data['files'])) {
            foreach ($data['files'] as $file) {
                if (is_object($file) && method_exists($file, 'isValid') && $file->isValid()) {
                    $filesToStore[] = $file;
                }
            }
            // Jangan unset, biarkan di $data untuk di-handle di Controller
        }

        // Handle file deletion
        if (isset($data['deleted_files']) && is_array($data['deleted_files'])) {
            foreach ($data['deleted_files'] as $fileId) {
                $file = AduanMasyarakatFile::find($fileId);
                // Validasi: file harus ada dan milik record yang sedang diupdate (jika update)
                if ($file && (!$record || $file->aduan_masyarakat_id == $record->id)) {
                    // Hapus file dari storage
                    if (Storage::disk('public')->exists($file->file_path)) {
                        Storage::disk('public')->delete($file->file_path);
                    }
                    // Hapus record dari database
                    $file->delete();
                }
            }
            unset($data['deleted_files']);
        }

        return $data;
    }

    public function storeFiles($record, $files)
    {
        if (empty($files) || !is_array($files)) {
            return;
        }

        foreach ($files as $file) {
            if (is_object($file) && method_exists($file, 'isValid') && $file->isValid()) {
                $fileType = str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'foto';
                $path = $file->store('aduan-masyarakat', 'public');
                
                AduanMasyarakatFile::create([
                    'aduan_masyarakat_id' => $record->id,
                    'file_path'           => $path,
                    'file_type'           => $fileType,
                    'file_name'           => $file->getClientOriginalName(),
                    'created_by'          => auth()->id(),
                ]);
            }
        }
    }
}

