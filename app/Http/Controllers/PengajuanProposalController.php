<?php

namespace App\Http\Controllers;

use App\Http\Requests\PengajuanProposalRequest;
use App\Repositories\PengajuanProposalRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class PengajuanProposalController extends Controller implements HasMiddleware
{
    use BaseTrait;
    
    private $repository;
    private $request;

    public function __construct(PengajuanProposalRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request = PengajuanProposalRequest::createFromBase($request);
        $this->initialize();
        $this->route = 'pengajuan-proposal';
        $this->commonData['kode_first_menu'] = 'PENGAJUAN-PROPOSAL';
        $this->commonData['kode_second_menu'] = $this->kode_menu;
    }

    public static function middleware(): array
    {
        $className = class_basename(__CLASS__);
        $permission = str_replace('Controller', '', $className);
        $permission = trim(implode(' ', preg_split('/(?=[A-Z])/', $permission)));
        return [
            new Middleware("can:$permission Show", only: ['index']),
            new Middleware("can:$permission Add", only: ['create', 'store']),
            new Middleware("can:$permission Detail", only: ['show']),
            new Middleware("can:$permission Edit", only: ['edit', 'update']),
            new Middleware("can:$permission Delete", only: ['destroy', 'destroy_selected']),
            new Middleware("can:$permission Verifikasi", only: ['verifikasi', 'storeVerifikasi']),
            new Middleware("can:$permission Export PDF", only: ['exportPdf']),
            new Middleware("can:$permission Preview PDF", only: ['previewPdf']),
            // Pengajuan Saya
            new Middleware("can:Pengajuan Proposal Saya Show", only: ['indexPengajuanSaya', 'createPengajuanSaya', 'showPengajuanSaya', 'editPengajuanSaya', 'apiIndexPengajuanSaya']),
            new Middleware("can:Pengajuan Proposal Saya Add", only: ['store']),
            new Middleware("can:Pengajuan Proposal Saya Edit", only: ['update']),
        ];
    }

    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);
        return response()->json([
            'data' => $data['pengajuan_proposal'] ?? [],
            'meta' => $data['meta'] ?? [],
        ]);
    }

    // ========== PENGAJUAN SAYA (USER) ==========
    
    public function apiIndexPengajuanSaya()
    {
        $data = $this->repository->customIndex([], true);
        return response()->json([
            'data' => $data['pengajuan_proposal'] ?? [],
            'meta' => $data['meta'] ?? [],
        ]);
    }

    public function apiShow($id)
    {
        $item = $this->repository->getById($id);
        if (!$item) {
            return response()->json(['error' => 'Pengajuan Proposal not found'], 404);
        }

        $data = $this->commonData + ['item' => $item];
        $data = $this->repository->customShow($data, $item);
        
        // Convert item to array untuk JSON response
        if (isset($data['item']) && is_object($data['item'])) {
            $data['item'] = $data['item']->toArray();
        }
        
        return response()->json($data);
    }

    public function indexPengajuanSaya()
    {
        $data = $this->commonData + [];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customIndex($data, true);
        return inertia('modules/pengajuan-proposal/pengajuan-saya/Index', $data);
    }

    public function createPengajuanSaya()
    {
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + [
            'item' => null,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data);
        $data['listKategoriProposal'] = \App\Models\KategoriProposal::all()->pluck('nama', 'id');
        $data['listResident'] = \App\Models\Residents::select('id', 'nama', 'nik')->get()->map(function ($r) {
            return ['id' => $r->id, 'label' => $r->nama . ' (' . $r->nik . ')' ];
        });
        return inertia('modules/pengajuan-proposal/pengajuan-saya/Create', $data);
    }

    public function showPengajuanSaya($id)
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getFind($id);
        
        if (!$item) {
            abort(404);
        }

        // Check ownership
        $user = Auth::user();
        if ($user && $user->resident_id) {
            if ($item->resident_id != $user->resident_id) {
                abort(403);
            }
        } else {
            if ($item->created_by != $user->id) {
                abort(403);
            }
        }

        $data = $this->commonData + [
            'item' => $item,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customShow($data, $item);
        return inertia('modules/pengajuan-proposal/pengajuan-saya/Show', $data);
    }

    public function editPengajuanSaya($id)
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getFind($id);
        
        if (!$item) {
            abort(404);
        }

        // Check ownership and status
        $user = Auth::user();
        if ($user && $user->resident_id) {
            if ($item->resident_id != $user->resident_id) {
                abort(403);
            }
        } else {
            if ($item->created_by != $user->id) {
                abort(403);
            }
        }

        if (!$item->canBeEdited()) {
            return redirect()->route('pengajuan-proposal-saya.show', $id)
                ->with('error', 'Pengajuan tidak dapat diedit karena sudah diverifikasi.');
        }

        $data = $this->commonData + [
            'item' => $item,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data, $item);
        $data['listKategoriProposal'] = \App\Models\KategoriProposal::all()->pluck('nama', 'id');
        $data['listResident'] = \App\Models\Residents::select('id', 'nama', 'nik')->get()->map(function ($r) {
            return ['id' => $r->id, 'label' => $r->nama . ' (' . $r->nik . ')' ];
        });
        return inertia('modules/pengajuan-proposal/pengajuan-saya/Edit', $data);
    }

    // Override store untuk handle pengajuan saya
    public function store(Request $request)
    {
        $this->request = $request;
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->request->validate($this->getValidationRules());
        $data = $this->request->all();

        $isPengajuanSaya = $request->routeIs('pengajuan-proposal-saya.*');

        // Process file uploads
        if ($request->hasFile('file_pendukung')) {
            $data['file_pendukung'] = $request->file('file_pendukung');
        }

        // Process existing files
        if ($request->has('existing_files')) {
            $data['existing_files'] = $request->input('existing_files', []);
        }

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

        if ($isPengajuanSaya) {
            return redirect()->route('pengajuan-proposal-saya.index')->with('success', 'Pengajuan proposal berhasil dibuat.');
        }
        return redirect()->route($this->route . '.index')->with('success', trans('message.success_add'));
    }

    // Override update untuk handle pengajuan saya
    public function update()
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $this->request->id]);
        $data = $this->request->validate($this->request->rules());
        $data = $this->request->all();

        $isPengajuanSaya = $this->request->routeIs('pengajuan-proposal-saya.*');

        // Process file uploads
        if ($this->request->hasFile('file_pendukung')) {
            $data['file_pendukung'] = $this->request->file('file_pendukung');
        }

        // Process existing files
        if ($this->request->has('existing_files')) {
            $data['existing_files'] = $this->request->input('existing_files', []);
        }

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

        if ($isPengajuanSaya) {
            return redirect()->route('pengajuan-proposal-saya.show', $this->request->id)->with('success', 'Pengajuan proposal berhasil diupdate.');
        }
        return redirect()->route($this->route . '.index')->with('success', trans('message.success_update'));
    }

    // Override create untuk menambahkan data yang diperlukan
    public function create()
    {
        $this->repository->customProperty(__FUNCTION__);
        $data = $this->commonData + [
            'item' => null,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data);
        $data['listKategoriProposal'] = \App\Models\KategoriProposal::all()->pluck('nama', 'id');
        $data['listResident'] = \App\Models\Residents::select('id', 'nama', 'nik')->get()->map(function ($r) {
            return ['id' => $r->id, 'label' => $r->nama . ' (' . $r->nik . ')' ];
        });
        if (!is_array($data)) {
            return $data;
        }
        return inertia("modules/$this->route/Create", $data);
    }

    // Override edit untuk menambahkan data yang diperlukan
    public function edit($id = '')
    {
        $this->repository->customProperty(__FUNCTION__, ['id' => $id]);
        $item = $this->repository->getById($id);
        $data = $this->commonData + [
            'item' => $item,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customCreateEdit($data, $item);
        $data['listKategoriProposal'] = \App\Models\KategoriProposal::all()->pluck('nama', 'id');
        $data['listResident'] = \App\Models\Residents::select('id', 'nama', 'nik')->get()->map(function ($r) {
            return ['id' => $r->id, 'label' => $r->nama . ' (' . $r->nik . ')' ];
        });
        if (!is_array($data)) {
            return $data;
        }
        return inertia("modules/$this->route/Edit", $data);
    }

    // ========== VERIFIKASI ==========

    public function verifikasi($id)
    {
        $item = $this->repository->getFind($id);
        
        if (!$item) {
            abort(404);
        }

        if (!in_array($item->status, ['menunggu_verifikasi'])) {
            return redirect()->route('pengajuan-proposal.show', $id)
                ->with('error', 'Pengajuan proposal sudah diverifikasi.');
        }

        $data = $this->commonData + [
            'item' => $item,
        ];
        if ($this->check_permission == true) {
            $data = array_merge($data, $this->getPermission());
        }
        $data = $this->repository->customShow($data, $item);
        
        // Get admin TTD digital if exists
        $user = Auth::user();
        $data['admin_ttd_digital'] = $user->tanda_tangan_digital ? [
            'id' => $user->id,
            'tanda_tangan_digital' => $user->tanda_tangan_digital,
        ] : null;
        
        return inertia('modules/pengajuan-proposal/Verifikasi', $data);
    }

    public function storeVerifikasi(Request $request, $id)
    {
        $item = $this->repository->getFind($id);
        
        if (!$item) {
            abort(404);
        }

        if (!in_array($item->status, ['menunggu_verifikasi'])) {
            return redirect()->route('pengajuan-proposal.show', $id)
                ->with('error', 'Pengajuan proposal sudah diverifikasi.');
        }

        $request->validate([
            'status' => 'required|in:disetujui,ditolak',
            'catatan_verifikasi' => 'nullable|string',
            'tanda_tangan_type' => 'required_if:status,disetujui|in:digital,foto',
            'use_existing_ttd' => 'nullable|in:yes,no',
            'tanda_tangan_digital' => 'required_if:tanda_tangan_type,digital|nullable|string',
            'foto_tanda_tangan' => 'required_if:tanda_tangan_type,foto|nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $data = [
            'status' => $request->status,
            'catatan_verifikasi' => $request->catatan_verifikasi,
            'admin_verifikasi_id' => Auth::id(),
            'tanggal_diverifikasi' => now(),
        ];

        if ($request->status === 'disetujui') {
            if ($request->tanda_tangan_type === 'digital') {
                if ($request->use_existing_ttd === 'yes') {
                    $user = Auth::user();
                    $data['tanda_tangan_digital'] = $user->tanda_tangan_digital;
                } else {
                    $data['tanda_tangan_digital'] = $request->tanda_tangan_digital;
                }
            } else {
                if ($request->use_existing_ttd === 'yes') {
                    $user = Auth::user();
                    $data['foto_tanda_tangan'] = $user->foto_tanda_tangan;
                } else {
                    if ($request->hasFile('foto_tanda_tangan')) {
                        $data['foto_tanda_tangan'] = $request->file('foto_tanda_tangan')->store('pengajuan-proposal/ttd', 'public');
                    }
                }
            }
        }

        $this->repository->update($id, $data);

        return redirect()->route('pengajuan-proposal.show', $id)
            ->with('success', 'Pengajuan proposal berhasil diverifikasi.');
    }

    /**
     * Preview PDF
     */
    public function previewPdf($id)
    {
        $pengajuan = $this->repository->getById($id);
        
        if ($pengajuan->status !== 'disetujui') {
            return redirect()->route('pengajuan-proposal.show', $id)
                ->with('error', 'Pengajuan proposal belum disetujui.');
        }

        // Load relationships
        $pengajuan->load(['kategoriProposal', 'resident', 'adminVerifikasi']);

        // Get data untuk PDF
        $data = $this->repository->customShow([], $pengajuan);

        $pdf = Pdf::loadView('pdf.pengajuan-proposal', [
            'pengajuan' => $pengajuan,
            'item' => $data['item'] ?? [],
        ])->setPaper('a4', 'portrait');

        $filename = 'proposal-' . $pengajuan->id . '-preview.pdf';
        return $pdf->stream($filename);
    }

    /**
     * Export PDF
     */
    public function exportPdf($id)
    {
        $pengajuan = $this->repository->getById($id);
        
        if ($pengajuan->status !== 'disetujui') {
            return redirect()->route('pengajuan-proposal.show', $id)
                ->with('error', 'Pengajuan proposal belum disetujui.');
        }

        // Load relationships
        $pengajuan->load(['kategoriProposal', 'resident', 'adminVerifikasi']);

        // Get data untuk PDF
        $data = $this->repository->customShow([], $pengajuan);

        $pdf = Pdf::loadView('pdf.pengajuan-proposal', [
            'pengajuan' => $pengajuan,
            'item' => $data['item'] ?? [],
        ])->setPaper('a4', 'portrait');

        $filename = 'proposal-' . $pengajuan->id . '.pdf';
        return $pdf->download($filename);
    }

    private function getPermission()
    {
        $auth_user = Auth::user();
        $permission_main = $this->permission_main;
        
        return [
            'can' => [
                'Add'    => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($permission_main . ' Add') : false,
                'Edit'   => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($permission_main . ' Edit') : false,
                'Delete' => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($permission_main . ' Delete') : false,
                'Detail' => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($permission_main . ' Detail') : false,
                'Verifikasi' => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($permission_main . ' Verifikasi') : false,
                'Export PDF' => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($permission_main . ' Export PDF') : false,
                'Preview PDF' => $auth_user && method_exists($auth_user, 'can') ? $auth_user->can($permission_main . ' Preview PDF') : false,
            ],
        ];
    }
}

