<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtributJenisSuratRequest;
use App\Repositories\AtributJenisSuratRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Database\Eloquent\Model;

class AtributJenisSuratController extends Controller implements HasMiddleware
{
    use BaseTrait;
    
    private $repository;
    private $request;

    public function __construct(AtributJenisSuratRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = AtributJenisSuratRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'layanan-surat/atribut-jenis-surat';
        $this->commonData['kode_first_menu']  = 'LAYANAN-SURAT';
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
            'data' => $data['atribut_jenis_surat'] ?? [],
            'meta' => [
                'total'        => $data['meta']['total'] ?? 0,
                'current_page' => $data['meta']['current_page'] ?? 1,
                'per_page'     => $data['meta']['per_page'] ?? 10,
                'search'       => $data['meta']['search'] ?? '',
                'sort'         => $data['meta']['sort'] ?? '',
                'order'        => $data['meta']['order'] ?? 'asc',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->request = $request;
        $this->repository->customProperty(__FUNCTION__);
        $data   = $this->request->validate($this->getValidationRules());
        $data   = $this->request->all();
        $before = $this->repository->callbackBeforeStoreOrUpdate($data, 'store');
        if ($before['error'] != 0) {
            return redirect()->back()->with('error', $before['message'])->withInput();
        } else {
            $data = $before['data'];
        }
        $model = $this->repository->create($data);
        if (!($model instanceof Model)) {
            return $model;
        }
        return redirect()->route('atribut-jenis-surat.index')->with('success', trans('message.success_add'));
    }

    public function update()
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $this->request->id]);
        $data   = $this->request->validate($this->request->rules());
        $data   = $this->request->all();
        $before = $this->repository->callbackBeforeStoreOrUpdate($data, 'update');
        if ($before['error'] != 0) {
            return redirect()->back()->with('error', $before['message'])->withInput();
        } else {
            $data = $before['data'];
        }
        $model = $this->repository->update($this->request->id, $data);
        if (!($model instanceof Model)) {
            return $model;
        }
        return redirect()->route('atribut-jenis-surat.index')->with('success', trans('message.success_update'));
    }

    public function destroy($id)
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $model    = $this->repository->delete($id);
        $callback = $this->repository->callbackAfterDelete($model, $id);
        if (!($callback instanceof Model)) {
            return $callback;
        }
        return redirect()->route('atribut-jenis-surat.index')->with('success', trans('message.success_delete'));
    }
}

