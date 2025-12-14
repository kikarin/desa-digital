<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssistanceProgramItemsRequest;
use App\Repositories\AssistanceProgramItemsRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;

class AssistanceProgramItemsController extends Controller implements HasMiddleware
{
    use BaseTrait;
    
    private $repository;
    private $request;

    /**
     * Constructor - inject repository dan request
     */
    public function __construct(AssistanceProgramItemsRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = AssistanceProgramItemsRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'assistance-program-items';
        $this->commonData['kode_first_menu']  = 'PROGRAM-BANTUAN';
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
     * Override store method untuk handle error dengan message yang user-friendly
     */
    public function store(Request $request)
    {
        $this->request = AssistanceProgramItemsRequest::createFromBase($request);
        $this->repository->customProperty(__FUNCTION__);
        
        try {
            $data = $this->request->validate($this->getValidationRules());
            $data = $this->request->all();
            $before = $this->repository->callbackBeforeStoreOrUpdate($data, 'store');
            if ($before['error'] != 0) {
                return redirect()->back()->with('error', $before['message'])->withInput();
            } else {
                $data = $before['data'];
            }
            $model = $this->repository->create($data);
            if (!($model instanceof \Illuminate\Database\Eloquent\Model)) {
                return $model;
            }
            return redirect()->route($this->route . '.index')->with('success', trans('message.success_add'));
        } catch (ValidationException $e) {
            // Re-throw validation exception as-is
            throw $e;
        } catch (\Exception $e) {
            // Handle unique constraint violation atau error lainnya
            $message = $e->getMessage();
            
            // Jika error message sudah user-friendly, gunakan itu
            if (str_contains($message, 'sudah ditambahkan')) {
                throw ValidationException::withMessages([
                    'assistance_item_id' => $message,
                ]);
            }
            
            // Jika error dari database constraint, tampilkan message yang user-friendly
            if (str_contains($message, 'Duplicate entry') || str_contains($message, 'unique_program_item')) {
                throw ValidationException::withMessages([
                    'assistance_item_id' => 'Item ini sudah ditambahkan ke program ini.',
                ]);
            }
            
            // Untuk error lainnya, throw kembali exception asli
            throw $e;
        }
    }

    /**
     * Override update method untuk handle error dengan message yang user-friendly
     */
    public function update()
    {
        // Refresh request dari current request
        $this->request = AssistanceProgramItemsRequest::createFromBase(request());
        $this->repository->customProperty(__FUNCTION__, ['id' => $this->request->id]);
        
        try {
            $data = $this->request->validate($this->request->rules());
            $data = $this->request->all();
            $before = $this->repository->callbackBeforeStoreOrUpdate($data, 'update');
            if ($before['error'] != 0) {
                return redirect()->back()->with('error', $before['message'])->withInput();
            } else {
                $data = $before['data'];
            }
            $model = $this->repository->update($this->request->id, $data);
            if (!($model instanceof \Illuminate\Database\Eloquent\Model)) {
                return $model;
            }
            return redirect()->route($this->route . '.index')->with('success', trans('message.success_update'));
        } catch (ValidationException $e) {
            // Re-throw validation exception as-is
            throw $e;
        } catch (\Exception $e) {
            // Handle unique constraint violation atau error lainnya
            $message = $e->getMessage();
            
            // Jika error message sudah user-friendly, gunakan itu
            if (str_contains($message, 'sudah ditambahkan')) {
                throw ValidationException::withMessages([
                    'assistance_item_id' => $message,
                ]);
            }
            
            // Jika error dari database constraint, tampilkan message yang user-friendly
            if (str_contains($message, 'Duplicate entry') || str_contains($message, 'unique_program_item')) {
                throw ValidationException::withMessages([
                    'assistance_item_id' => 'Item ini sudah ditambahkan ke program ini.',
                ]);
            }
            
            // Untuk error lainnya, throw kembali exception asli
            throw $e;
        }
    }

    /**
     * API endpoint untuk data table
     */
    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);
        return response()->json([
            'data' => $data['assistance_program_items'],
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

