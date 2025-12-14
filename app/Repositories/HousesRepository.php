<?php

namespace App\Repositories;

use App\Models\Houses;
use App\Traits\RepositoryTrait;
use App\Repositories\RtsRepository;
use Illuminate\Support\Facades\Storage;

class HousesRepository
{
    use RepositoryTrait;

    protected $model;

    public function __construct(Houses $model)
    {
        $this->model = $model;
        $this->with = ['rt.rw', 'created_by_user', 'updated_by_user'];
    }

    public function customIndex($data)
    {
        $query = $this->model->with(['rt.rw', 'families.residents' => function ($query) {
            $query->with('status');
        }])
            ->select('houses.id', 'houses.rt_id', 'houses.nomor_rumah', 'houses.jenis_rumah', 'houses.keterangan', 'houses.fotos',
                     'rts.nomor_rt', 'rws.nomor_rw', 'rws.desa', 'rws.kecamatan', 'rws.kabupaten')
            ->leftJoin('rts', 'houses.rt_id', '=', 'rts.id')
            ->leftJoin('rws', 'rts.rw_id', '=', 'rws.id');

        if (request('filter_rw_id')) {
            $query->where('rws.id', request('filter_rw_id'));
        }

        if (request('filter_rt_id')) {
            $query->where('houses.rt_id', request('filter_rt_id'));
        }

        if (request('filter_jenis_rumah')) {
            $query->where('houses.jenis_rumah', request('filter_jenis_rumah'));
        }

        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('houses.nomor_rumah', 'like', '%' . $searchTerm . '%')
                    ->orWhere('houses.jenis_rumah', 'like', '%' . $searchTerm . '%')
                    ->orWhere('houses.keterangan', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rts.nomor_rt', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rws.nomor_rw', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rws.desa', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rws.kecamatan', 'like', '%' . $searchTerm . '%')
                    ->orWhere('rws.kabupaten', 'like', '%' . $searchTerm . '%');
            });
        }

        if (request('sort')) {
            $order = request('order', 'asc');
            $sortMapping = [
                'nomor_rumah' => 'houses.nomor_rumah',
                'jenis_rumah' => 'houses.jenis_rumah',
                'rt'          => 'rts.nomor_rt',
                'rw'          => 'rws.nomor_rw',
                'desa'        => 'rws.desa',
                'kecamatan'   => 'rws.kecamatan',
                'kabupaten'   => 'rws.kabupaten',
            ];

            $sortColumn = $sortMapping[request('sort')] ?? 'houses.id';
            $query->orderBy($sortColumn, $order);
        } else {
            $query->orderBy('houses.id', 'desc');
        }

        $perPage = (int) request('per_page', 10);
        
        if ($perPage === -1) {
            $allHouses = $query->get();
            $transformedHouses = $allHouses->map(function ($house) {
                $totalResidents = 0;
                foreach ($house->families as $family) {
                    foreach ($family->residents as $resident) {
                        $statusName = $resident->status->name ?? '';
                        if ($statusName !== 'Meninggal' && $statusName !== 'Pindah') {
                            $totalResidents++;
                        }
                    }
                }
                
                return [
                    'id'          => $house->id,
                    'nomor_rumah' => $house->nomor_rumah,
                    'jenis_rumah' => $house->jenis_rumah,
                    'rt_id'       => $house->rt_id,
                    'rt'          => $house->nomor_rt,
                    'rw'          => $house->nomor_rw . ' - ' . $house->desa,
                    'desa'        => $house->desa,
                    'kecamatan'   => $house->kecamatan,
                    'kabupaten'  => $house->kabupaten,
                    'keterangan' => $house->keterangan,
                    'total_residents' => $totalResidents,
                    'foto_url' => $this->getFirstFotoUrl($house),
                ];
            });

            $data += [
                'houses' => $transformedHouses,
                'meta' => [
                    'total'        => $transformedHouses->count(),
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
        $houses         = $query->paginate($perPage, ['*'], 'page', $pageForLaravel);

        $transformedHouses = $houses->getCollection()->map(function ($house) {
            $totalResidents = 0;
            foreach ($house->families as $family) {
                foreach ($family->residents as $resident) {
                    $statusName = $resident->status->name ?? '';
                    if ($statusName !== 'Meninggal' && $statusName !== 'Pindah') {
                        $totalResidents++;
                    }
                }
            }
            
            return [
                'id'          => $house->id,
                'nomor_rumah' => $house->nomor_rumah,
                'jenis_rumah' => $house->jenis_rumah,
                'rt_id'       => $house->rt_id,
                'rt'          => $house->nomor_rt,
                'rw'          => $house->nomor_rw . ' - ' . $house->desa,
                'desa'        => $house->desa,
                'kecamatan'   => $house->kecamatan,
                'kabupaten'  => $house->kabupaten,
                    'keterangan' => $house->keterangan,
                    'total_residents' => $totalResidents,
                    'foto_url' => $this->getFirstFotoUrl($house),
                ];
            });

        $data += [
            'houses' => $transformedHouses,
            'meta' => [
                'total'        => $houses->total(),
                'current_page' => $houses->currentPage(),
                'per_page'     => $houses->perPage(),
                'search'       => request('search', ''),
                'sort'         => request('sort', ''),
                'order'        => request('order', 'asc'),
            ],
        ];

        return $data;
    }

    public function customDataCreateUpdate($data, $record = null)
    {
        // Hapus fotos dan deleted_media_ids dari data karena akan di-handle di callbackAfterStoreOrUpdate
        unset($data['fotos']);
        unset($data['deleted_media_ids']);
        return $data;
    }

    public function customCreateEdit($data, $item = null)
    {
        $rtsRepository = app(RtsRepository::class);
        $rts = $rtsRepository->getAll([], false, false);
        
        $rts->load('rw');
        
        $residentsRepository = app(\App\Repositories\ResidentsRepository::class);
        $residents = $residentsRepository->getAll([], false, false);
        
        $data += [
            'listRt' => $rts->map(function ($rt) {
                $rw = $rt->rw;
                $label = $rt->nomor_rt;
                if ($rw) {
                    $label .= ' - RW ' . $rw->nomor_rw . ' - ' . $rw->desa;
                }
                return [
                    'value' => $rt->id,
                    'label' => $label,
                ];
            })->toArray(),
            'listResidents' => $residents->map(function ($resident) {
                return [
                    'value' => $resident->id,
                    'label' => $resident->nik . ' - ' . $resident->nama,
                ];
            })->toArray(),
        ];

        // Load fotos untuk edit
        if ($item) {
            // Reload item untuk memastikan fotos ter-load
            $item->refresh();
            $data['fotos'] = $this->getFotosArray($item);
        }

        return $data;
    }

    public function customShow($data, $item = null)
    {
        if ($item) {
            $item->load([
                'rt.rw', // Pastikan rt.rw ter-load
                'families.residents' => function ($query) {
                    $query->with('status');
                }, 
                'families.kepala_keluarga',
                'pemilik'
            ]);
            
            // Reload item untuk memastikan fotos ter-load
            $item->refresh();
            
            // Load fotos untuk show - pastikan ter-pass ke item juga
            $fotos = $this->getFotosArray($item);
            $data['fotos'] = $fotos;
            
            // Pastikan item juga punya fotos untuk kompatibilitas
            if (isset($data['item']) && is_object($data['item'])) {
                $data['item']->fotos = $fotos;
            }
            
            $residents = [];
            foreach ($item->families as $family) {
                foreach ($family->residents as $resident) {
                    $residents[] = [
                        'id' => $resident->id,
                        'nik' => $resident->nik,
                        'nama' => $resident->nama,
                        'status' => $resident->status->name ?? '-',
                        'family_id' => $family->id,
                        'no_kk' => $family->no_kk,
                        'is_kepala_keluarga' => $resident->id === $family->kepala_keluarga_id,
                        'jenis_kelamin' => $resident->jenis_kelamin,
                        'tanggal_lahir' => $resident->tanggal_lahir,
                    ];
                }
            }
            
            $data['residents'] = $residents;
        }
        
        return $data;
    }

    /**
     * Helper method untuk mendapatkan array fotos
     */
    private function getFotosArray($item)
    {
        $fotos = [];
        if (!empty($item->fotos) && is_array($item->fotos)) {
            $fotos = array_map(function($path, $index) {
                // Pastikan path valid dan file exists
                $fileExists = Storage::disk('public')->exists($path);
                
                // Gunakan asset() helper yang lebih reliable untuk generate URL
                // asset() akan menggunakan APP_URL dari config
                $url = $fileExists ? asset('storage/' . $path) : null;
                
                return [
                    'id' => $index, // Gunakan index sebagai ID untuk kompatibilitas dengan frontend (untuk delete)
                    'url' => $url,
                    'name' => basename($path),
                ];
            }, array_filter($item->fotos), array_keys(array_filter($item->fotos)));
        }
        return $fotos;
    }

    /**
     * Helper method untuk mendapatkan URL foto pertama (untuk thumbnail di index)
     */
    private function getFirstFotoUrl($house)
    {
        if (!empty($house->fotos) && is_array($house->fotos) && count($house->fotos) > 0) {
            $firstFoto = $house->fotos[0];
            if (Storage::disk('public')->exists($firstFoto)) {
                return asset('storage/' . $firstFoto);
            }
        }
        return null;
    }

    /**
     * Handle upload dan delete foto setelah store/update
     */
    public function callbackAfterStoreOrUpdate($model, $data, $method = 'store', $record_sebelumnya = null)
    {
        $request = request();
        
        // Handle upload multiple foto
        // Cek apakah ada file dengan nama fotos atau fotos[]
        if ($request->hasFile('fotos')) {
            $fotos = $request->file('fotos');
            // Jika hanya 1 file, wrap dalam array
            if (!is_array($fotos)) {
                $fotos = [$fotos];
            }
            
            $fotoPaths = $model->fotos ?? [];
            
            foreach ($fotos as $foto) {
                if ($foto && $foto->isValid()) {
                    try {
                        // Simpan di storage/app/public/houses/{house_id}/
                        $path = $foto->store('houses/' . $model->id, 'public');
                        if ($path) {
                            $fotoPaths[] = $path;
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error storing foto: ' . $e->getMessage());
                    }
                }
            }
            
            if (!empty($fotoPaths)) {
                $model->update(['fotos' => $fotoPaths]);
            }
        } elseif ($request->hasFile('fotos.0')) {
            // Handle jika file dikirim sebagai array fotos[0], fotos[1], etc
            $fotoPaths = $model->fotos ?? [];
            $index = 0;
            while ($request->hasFile("fotos.$index")) {
                $foto = $request->file("fotos.$index");
                if ($foto && $foto->isValid()) {
                    try {
                        $path = $foto->store('houses/' . $model->id, 'public');
                        if ($path) {
                            $fotoPaths[] = $path;
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error storing foto: ' . $e->getMessage());
                    }
                }
                $index++;
            }
            if (!empty($fotoPaths)) {
                $model->update(['fotos' => $fotoPaths]);
            }
        }

        // Handle delete foto yang dipilih
        $deletedMediaIds = $request->input('deleted_media_ids', []);
        if (is_array($deletedMediaIds) && !empty($deletedMediaIds)) {
            $fotoPaths = $model->fotos ?? [];
            foreach ($deletedMediaIds as $index) {
                if (isset($fotoPaths[$index])) {
                    // Hapus file dari storage
                    Storage::disk('public')->delete($fotoPaths[$index]);
                    unset($fotoPaths[$index]);
                }
            }
            // Re-index array setelah delete
            $model->update(['fotos' => array_values($fotoPaths)]);
        }

        return $model;
    }
}

