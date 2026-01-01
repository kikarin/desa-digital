<?php

namespace App\Http\Controllers;

use App\Http\Requests\HousesRequest;
use App\Repositories\HousesRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Repositories\RwsRepository;
use App\Repositories\RtsRepository;

class HousesController extends Controller implements HasMiddleware
{
    use BaseTrait;
    
    private $repository;
    private $request;

    public function __construct(HousesRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = HousesRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'houses';
        $this->commonData['kode_first_menu']  = 'DATA-WARGA';
        $this->commonData['kode_second_menu'] = $this->kode_menu;
    }

    public static function middleware(): array
    {
        $className  = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));
        return [
            new Middleware("can:$permission Show", only: ['index']),
            new Middleware("can:$permission Add", only: ['create', 'store']),
            new Middleware("can:$permission Detail", only: ['show']),
            new Middleware("can:$permission Edit", only: ['edit', 'update']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
        ];
    }

    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);
        
        $rwsRepository = app(RwsRepository::class);
        $rws = $rwsRepository->getAll([], false, false);
        
        $rtsRepository = app(RtsRepository::class);
        $rts = $rtsRepository->getAll([], false, false);
        $rts->load('rw');
        
        return response()->json([
            'data' => $data['houses'],
            'meta' => [
                'total'        => $data['meta']['total'],
                'current_page' => $data['meta']['current_page'],
                'per_page'     => $data['meta']['per_page'],
                'search'       => $data['meta']['search'],
                'sort'         => $data['meta']['sort'],
                'order'        => $data['meta']['order'],
            ],
            'filterOptions' => [
                'rw' => $rws->map(function ($rw) {
                    return [
                        'value' => $rw->id,
                        'label' => $rw->nomor_rw . ' - ' . $rw->desa . ', ' . $rw->kecamatan . ', ' . $rw->kabupaten,
                    ];
                })->toArray(),
                'rt' => $rts->map(function ($rt) {
                    $rw = $rt->rw;
                    $label = $rt->nomor_rt;
                    if ($rw) {
                        $label .= ' - RW ' . $rw->nomor_rw . ' - ' . $rw->desa;
                    }
                    return [
                        'value' => $rt->id,
                        'label' => $label,
                        'rw_id' => $rt->rw_id,
                    ];
                })->toArray(),
                'jenis_rumah' => [
                    ['value' => 'RUMAH_TINGGAL', 'label' => 'Rumah Tinggal'],
                    ['value' => 'KONTRAKAN', 'label' => 'Kontrakan'],
                    ['value' => 'WARUNG_TOKO_USAHA', 'label' => 'Warung / Toko / Usaha'],
                    ['value' => 'FASILITAS_UMUM', 'label' => 'Fasilitas Umum'],
                ],
            ],
        ]);
    }

    public function apiShow($id)
    {
        $item = $this->repository->getById($id);
        if (!$item) {
            return response()->json(['error' => 'House not found'], 404);
        }

        $data = $this->commonData + [
            'item' => $item,
        ];
        $data = $this->repository->customShow($data, $item);
        
        // Convert item to array untuk JSON response
        if (isset($data['item']) && is_object($data['item'])) {
            $itemArray = $data['item']->toArray();
            // Pastikan families dan fotos ter-include
            if (isset($data['families'])) {
                $itemArray['families'] = $data['families'];
            }
            if (isset($data['fotos'])) {
                $itemArray['fotos'] = $data['fotos'];
            }
            $data['item'] = $itemArray;
        }
        
        return response()->json($data);
    }

    public function apiStats(Request $request)
    {
        $model = $this->repository->getInstanceModel();
        $query = $model::query();
        
        // Apply filters
        if ($request->has('rw_id') && $request->rw_id) {
            $query->whereHas('rt', function ($q) use ($request) {
                $q->where('rw_id', $request->rw_id);
            });
        }
        
        if ($request->has('rt_id') && $request->rt_id) {
            $query->where('rt_id', $request->rt_id);
        }
        
        $stats = $query->selectRaw('jenis_rumah, COUNT(*) as count')
            ->groupBy('jenis_rumah')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->jenis_rumah => $item->count];
            });

        // Get stats for other modules (with filters if needed)
        $bankSampahCount = \App\Models\BankSampah::count();
        $aduanMasyarakatCount = \App\Models\AduanMasyarakat::count();
        $layananDaruratCount = \App\Models\LayananDarurat::count();
        $pengajuanProposalCount = \App\Models\PengajuanProposal::count();

        return response()->json([
            'stats' => [
                'RUMAH_TINGGAL' => $stats['RUMAH_TINGGAL'] ?? 0,
                'KONTRAKAN' => $stats['KONTRAKAN'] ?? 0,
                'WARUNG_TOKO_USAHA' => $stats['WARUNG_TOKO_USAHA'] ?? 0,
                'FASILITAS_UMUM' => $stats['FASILITAS_UMUM'] ?? 0,
            ],
            'total' => array_sum($stats->toArray()),
            'modules' => [
                'bank_sampah' => $bankSampahCount,
                'aduan_masyarakat' => $aduanMasyarakatCount,
                'layanan_darurat' => $layananDaruratCount,
                'pengajuan_proposal' => $pengajuanProposalCount,
            ],
        ]);
    }
}

