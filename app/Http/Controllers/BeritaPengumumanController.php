<?php

namespace App\Http\Controllers;

use App\Http\Requests\BeritaPengumumanRequest;
use App\Repositories\BeritaPengumumanRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class BeritaPengumumanController extends Controller implements HasMiddleware
{
    use BaseTrait;
    
    private $repository;
    private $request;

    public function __construct(BeritaPengumumanRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = BeritaPengumumanRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'berita-pengumuman';
        $this->commonData['kode_first_menu']  = 'BERITA-PENGUMUMAN';
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
        return response()->json([
            'data' => $data['berita_pengumuman'],
            'meta' => [
                'total'        => $data['meta']['total'],
                'current_page' => $data['meta']['current_page'],
                'per_page'     => $data['meta']['per_page'],
                'search'       => $data['meta']['search'],
                'sort'         => $data['meta']['sort'],
                'order'        => $data['meta']['order'],
            ],
            'filterOptions' => [
                'tipe' => [
                    ['value' => 'berita', 'label' => 'Berita'],
                    ['value' => 'event', 'label' => 'Event'],
                ],
            ],
        ]);
    }
}

