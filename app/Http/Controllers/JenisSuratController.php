<?php

namespace App\Http\Controllers;

use App\Http\Requests\JenisSuratRequest;
use App\Repositories\JenisSuratRepository;
use App\Models\AtributJenisSurat;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class JenisSuratController extends Controller implements HasMiddleware
{
    use BaseTrait;
    
    private $repository;
    private $request;

    public function __construct(JenisSuratRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = JenisSuratRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'layanan-surat/jenis-surat';
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
            'data' => $data['jenis_surat'] ?? [],
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

    public function apiShow($id)
    {
        $item = $this->repository->getById($id);
        $data = $this->repository->customShow([], $item);
        return response()->json($data);
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

        try {
            DB::beginTransaction();

            // Extract atribut data
            $atributData = $data['atribut'] ?? [];
            unset($data['atribut']);

            // Create jenis surat
            $jenisSurat = $this->repository->create($data);

            // Save atribut
            foreach ($atributData as $atribut) {
                AtributJenisSurat::create([
                    'jenis_surat_id' => $jenisSurat->id,
                    'nama_atribut' => $atribut['nama_atribut'],
                    'tipe_data' => $atribut['tipe_data'],
                    'opsi_pilihan' => $atribut['opsi_pilihan'] ?? null,
                    'is_required' => $atribut['is_required'] ?? false,
                    'nama_lampiran' => $atribut['nama_lampiran'] ?? null,
                    'minimal_file' => $atribut['minimal_file'] ?? 0,
                    'is_required_lampiran' => $atribut['is_required_lampiran'] ?? false,
                    'urutan' => $atribut['urutan'] ?? 1,
                ]);
            }

            DB::commit();
            return redirect()->route('jenis-surat.index')->with('success', trans('message.success_add'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan: ' . $e->getMessage())->withInput();
        }
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

        try {
            DB::beginTransaction();

            // Extract atribut data
            $atributData = $data['atribut'] ?? [];
            unset($data['atribut']);

            // Update jenis surat
            $jenisSurat = $this->repository->update($this->request->id, $data);
            if (!($jenisSurat instanceof Model)) {
                return $jenisSurat;
            }

            // Get existing atribut IDs
            $existingAtributIds = AtributJenisSurat::where('jenis_surat_id', $this->request->id)
                ->pluck('id')
                ->toArray();

            // Get submitted atribut IDs
            $submittedAtributIds = array_filter(array_column($atributData, 'id'));

            // Delete atribut that are not in submitted data
            $atributToDelete = array_diff($existingAtributIds, $submittedAtributIds);
            if (!empty($atributToDelete)) {
                AtributJenisSurat::whereIn('id', $atributToDelete)->delete();
            }

            // Update or create atribut
            foreach ($atributData as $atribut) {
                $atributDataToSave = [
                    'jenis_surat_id' => $this->request->id,
                    'nama_atribut' => $atribut['nama_atribut'],
                    'tipe_data' => $atribut['tipe_data'],
                    'opsi_pilihan' => $atribut['opsi_pilihan'] ?? null,
                    'is_required' => $atribut['is_required'] ?? false,
                    'nama_lampiran' => $atribut['nama_lampiran'] ?? null,
                    'minimal_file' => $atribut['minimal_file'] ?? 0,
                    'is_required_lampiran' => $atribut['is_required_lampiran'] ?? false,
                    'urutan' => $atribut['urutan'] ?? 1,
                ];

                if (isset($atribut['id']) && in_array($atribut['id'], $existingAtributIds)) {
                    // Update existing
                    AtributJenisSurat::where('id', $atribut['id'])->update($atributDataToSave);
                } else {
                    // Create new
                    AtributJenisSurat::create($atributDataToSave);
                }
            }

            DB::commit();
            return redirect()->route('jenis-surat.index')->with('success', trans('message.success_update'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mengupdate: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $model    = $this->repository->delete($id);
        $callback = $this->repository->callbackAfterDelete($model, $id);
        if (!($callback instanceof Model)) {
            return $callback;
        }
        return redirect()->route('jenis-surat.index')->with('success', trans('message.success_delete'));
    }
}

