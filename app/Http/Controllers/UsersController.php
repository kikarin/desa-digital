<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsersRequest;
use App\Repositories\RoleRepository;
use App\Repositories\UsersRepository;
use App\Traits\BaseTrait;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Models\User;
use App\Repositories\UsersRoleRepository;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;
use App\Models\UsersRole;

class UsersController extends Controller implements HasMiddleware
{
    use BaseTrait;
    private $request;
    private $repository;
    private $roleRepository;

    public function __construct(Request $request, UsersRepository $repository, RoleRepository $roleRepository)
    {
        $this->repository     = $repository;
        $this->roleRepository = $roleRepository;
        $this->request        = UsersRequest::createFromBase($request);
        $this->initialize();
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
            new Middleware("can:$permission Login As", only: ['login_as']),
        ];
    }

    public function login_as($users_id = '')
    {
        $user            = $this->repository->loginAs($users_id);
        $init_page_login = ($user->role->init_page_login != '') ? $user->role->init_page_login : 'dashboard';
        return redirect($init_page_login)->withSuccess('Login As successfully.');
    }

    public function apiIndex()
    {
        $data = $this->repository->customIndex([]);

        return response()->json([
            'data' => $data['users'],
            'meta' => [
                'total'        => $data['total'],
                'current_page' => $data['currentPage'],
                'per_page'     => $data['perPage'],
                'search'       => $data['search'],
                'sort'         => $data['sort'],
                'order'        => $data['order'],
            ],
        ]);
    }

    public function store(UsersRequest $request)
    {
        $data = $this->repository->validateUserRequest($request);

        if (isset($data['role_id']) && is_array($data['role_id']) && count($data['role_id']) > 0) {
            $data['current_role_id'] = $data['role_id'][0];
        }

        $user = User::create($data);
        if (isset($data['role_id'])) {
            $user->roles()->sync($data['role_id']);
            
            // Prepare role data dengan rw_id dan rt_id
            $roleData = [];
            foreach ($data['role_id'] as $roleId) {
                $roleData[$roleId] = [];
                // Jika role adalah RW (35), tambahkan rw_id
                if ($roleId == 35 && isset($data['rw_id'])) {
                    $roleData[$roleId]['rw_id'] = $data['rw_id'];
                }
                // Jika role adalah RT (36), tambahkan rt_id
                if ($roleId == 36 && isset($data['rt_id'])) {
                    $roleData[$roleId]['rt_id'] = $data['rt_id'];
                }
            }
            
            app(UsersRoleRepository::class)->setRole($user->id, $data['role_id'], $roleData);
        }
        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan!');
    }

    public function update(UsersRequest $request, $id)
    {
        $data = $this->repository->validateUserRequest($request);

        if (isset($data['role_id']) && is_array($data['role_id']) && count($data['role_id']) > 0) {
            $data['current_role_id'] = $data['role_id'][0];
        }

        $user = User::findOrFail($id);
        $user->update($data);
        if (isset($data['role_id'])) {
            $user->roles()->sync($data['role_id']);
            
            // Prepare role data dengan rw_id dan rt_id
            $roleData = [];
            foreach ($data['role_id'] as $roleId) {
                $roleData[$roleId] = [];
                // Jika role adalah RW (35), tambahkan rw_id
                if ($roleId == 35 && isset($data['rw_id'])) {
                    $roleData[$roleId]['rw_id'] = $data['rw_id'];
                }
                // Jika role adalah RT (36), tambahkan rt_id
                if ($roleId == 36 && isset($data['rt_id'])) {
                    $roleData[$roleId]['rt_id'] = $data['rt_id'];
                }
            }
            
            app(UsersRoleRepository::class)->setRole($user->id, $data['role_id'], $roleData);
        }
        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui!');
    }

    public function show($id)
    {
        return $this->repository->handleShow($id);
    }

    public function switchRole(Request $request)
    {
        $request->validate([
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        $authUser  = Auth::user();
        $newRoleId = $request->input('role_id');

        $hasRole = UsersRole::where('users_id', $authUser->id)
                            ->where('role_id', $newRoleId)
                            ->exists();

        if (!$hasRole) {
            return back()->with('error', 'Invalid role.');
        }

        $user                  = User::find($authUser->id);
        $user->current_role_id = $newRoleId;
        $user->save();

        $newRole     = Role::find($newRoleId);
        $redirectUrl = $newRole && !empty($newRole->init_page_login) ? $newRole->init_page_login : 'dashboard';

        return redirect($redirectUrl)->with('success', 'Successfully switched role.');
    }
}
