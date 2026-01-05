<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResidentsRequest;
use App\Repositories\ResidentsRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Repositories\RwsRepository;
use App\Repositories\RtsRepository;
use App\Repositories\ResidentStatusRepository;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ResidentsImport;

class ResidentsController extends Controller implements HasMiddleware
{
    use BaseTrait;
    
    private $repository;
    private $request;

    public function __construct(ResidentsRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = ResidentsRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'residents';
        $this->commonData['kode_first_menu']  = 'DATA-WARGA';
        $this->commonData['kode_second_menu'] = $this->kode_menu;
    }

    public static function middleware(): array
    {
        $className  = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));
        return [
            new Middleware("can:$permission Show", only: ['index', 'apiIndex']),
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
        
        $residentStatusRepository = app(ResidentStatusRepository::class);
        $statuses = $residentStatusRepository->getAll([], false, false);
        
        return response()->json([
            'data' => $data['residents'],
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
                'status' => $statuses->map(function ($status) {
                    return [
                        'value' => $status->id,
                        'label' => $status->name,
                    ];
                })->toArray(),
            ],
        ]);
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:10240', // Max 10MB
        ]);

        try {
            $import = new ResidentsImport();
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $failCount = $import->getFailCount();
            $houseCount = $import->getHouseCount();
            $errors = $import->getErrors();

            $message = "Import selesai. Berhasil: {$successCount} resident, {$houseCount} house/fasilitas. Gagal: {$failCount}";
            
            if ($failCount > 0 && count($errors) > 0) {
                $message .= "\n\nError:\n" . implode("\n", array_slice($errors, 0, 10));
                if (count($errors) > 10) {
                    $message .= "\n... dan " . (count($errors) - 10) . " error lainnya.";
                }
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'success_count' => $successCount,
                'house_count' => $houseCount,
                'fail_count' => $failCount,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal import Excel: ' . $e->getMessage(),
            ], 422);
        }
    }
}

