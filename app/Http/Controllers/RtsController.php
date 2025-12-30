<?php

namespace App\Http\Controllers;

use App\Http\Requests\RtsRequest;
use App\Repositories\RtsRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Repositories\RwsRepository;
use App\Repositories\UsersRoleRepository;
use App\Models\UsersRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RtsController extends Controller implements HasMiddleware
{
    use BaseTrait;
    
    private $repository;
    private $request;

    public function __construct(RtsRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = RtsRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'rts';
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
        
        return response()->json([
            'data' => $data['rts'],
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
            ],
        ]);
    }

    /**
     * Buat akun untuk RT
     */
    public function createAccount($id)
    {
        try {
            $rt = $this->repository->getById($id);
            
            // Cek apakah sudah punya akun
            $existingAccount = UsersRole::where('rt_id', $rt->id)
                ->where('role_id', 36) // RT role ID
                ->first();
            
            if ($existingAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'RT ini sudah memiliki akun',
                ], 400);
            }
            
            // Generate email dan password (pastikan email unik)
            $baseEmail = 'rt' . str_replace(' ', '', strtolower($rt->nomor_rt)) . '@desa-digital';
            $email = $baseEmail;
            $counter = 1;
            while (User::where('email', $email)->exists()) {
                $email = 'rt' . str_replace(' ', '', strtolower($rt->nomor_rt)) . $counter . '@desa-digital';
                $counter++;
            }
            $password = 'RT' . $rt->nomor_rt . '2026!';
            $name = 'RT ' . $rt->nomor_rt;
            $noHp = '081234567890'; // Default no HP, bisa diubah nanti
            
            // Buat user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'no_hp' => $noHp,
                'is_active' => 1,
                'current_role_id' => 36, // RT role ID
            ]);
            
            // Assign role RT dengan rt_id
            $usersRoleRepository = app(UsersRoleRepository::class);
            $usersRoleRepository->setRole($user->id, [36], [
                36 => ['rt_id' => $rt->id]
            ]);
            
            // Sync dengan Spatie Permission
            $user->syncRoles([36]);
            
            return response()->json([
                'success' => true,
                'message' => 'Akun berhasil dibuat. Email: ' . $email . ', Password: ' . $password,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat akun: ' . $e->getMessage(),
            ], 500);
        }
    }
}

