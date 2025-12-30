<?php

namespace App\Http\Controllers;

use App\Http\Requests\RwsRequest;
use App\Repositories\RwsRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Models\UsersRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Repositories\UsersRoleRepository;

class RwsController extends Controller implements HasMiddleware
{
    use BaseTrait;
    
    private $repository;
    private $request;

    /**
     * Constructor - inject repository dan request
     */
    public function __construct(RwsRepository $repository, Request $request)
    {
        $this->repository = $repository;
        $this->request    = RwsRequest::createFromBase($request);
        $this->initialize();
        $this->route                          = 'rws';
        $this->commonData['kode_first_menu']  = 'DATA-WARGA';
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
            new Middleware("can:$permission Show", only: ['index']),
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
            'data' => $data['rws'],
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

    /**
     * Buat akun untuk RW
     */
    public function createAccount($id)
    {
        try {
            $rw = $this->repository->getById($id);
            
            // Cek apakah sudah punya akun
            $existingAccount = UsersRole::where('rw_id', $rw->id)
                ->where('role_id', 35) // RW role ID
                ->first();
            
            if ($existingAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'RW ini sudah memiliki akun',
                ], 400);
            }
            
            // Generate email dan password (pastikan email unik)
            $baseEmail = 'rw' . str_replace(' ', '', strtolower($rw->nomor_rw)) . '@desa-digital';
            $email = $baseEmail;
            $counter = 1;
            while (User::where('email', $email)->exists()) {
                $email = 'rw' . str_replace(' ', '', strtolower($rw->nomor_rw)) . $counter . '@desa-digital';
                $counter++;
            }
            $password = 'RW' . $rw->nomor_rw . '2026!';
            $name = 'RW ' . $rw->nomor_rw;
            $noHp = '081234567890'; // Default no HP, bisa diubah nanti
            
            // Buat user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'no_hp' => $noHp,
                'is_active' => 1,
                'current_role_id' => 35, // RW role ID
            ]);
            
            // Assign role RW dengan rw_id
            $usersRoleRepository = app(UsersRoleRepository::class);
            $usersRoleRepository->setRole($user->id, [35], [
                35 => ['rw_id' => $rw->id]
            ]);
            
            // Sync dengan Spatie Permission
            $user->syncRoles([35]);
            
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

