<?php

namespace App\Http\Controllers;

use App\Http\Requests\BankSampahRequest;
use App\Repositories\BankSampahRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class BankSampahController extends Controller implements HasMiddleware
{
    use BaseTrait;
    
    private $repository;
    private $request;

    public function __construct(BankSampahRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = BankSampahRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'bank-sampah';
        $this->commonData['kode_first_menu']  = 'BANK-SAMPAH';
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
            'data' => $data['bank_sampah'],
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

    public function apiShow($id)
    {
        $item = $this->repository->getById($id);
        if (!$item) {
            return response()->json(['error' => 'Bank Sampah not found'], 404);
        }

        $data = $this->commonData + ['item' => $item];
        $data = $this->repository->customShow($data, $item);
        
        return response()->json($data);
    }
}

