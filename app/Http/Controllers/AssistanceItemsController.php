<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssistanceItemsRequest;
use App\Repositories\AssistanceItemsRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AssistanceItemsController extends Controller implements HasMiddleware
{
    use BaseTrait;
    
    private $repository;
    private $request;

    /**
     * Constructor - inject repository dan request
     */
    public function __construct(AssistanceItemsRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = AssistanceItemsRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'assistance-items';
        $this->commonData['kode_first_menu']  = 'DATA-MASTER';
        $this->commonData['kode_second_menu'] = $this->kode_menu;
    }

    /**
     * Middleware untuk permission checking
     */
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

    /**
     * API endpoint untuk data table
     */
    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);
        return response()->json([
            'data' => $data['assistance_items'],
            'meta' => [
                'total'        => $data['meta']['total'],
                'current_page' => $data['meta']['current_page'],
                'per_page'     => $data['meta']['per_page'],
                'search'       => $data['meta']['search'],
                'sort'         => $data['meta']['sort'],
                'order'        => $data['meta']['order'],
            ],
        ]);
    }
}

