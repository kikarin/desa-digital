<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\UsersMenu;

class UsersMenuSeeder extends Seeder
{
    public function run(): void
    {
        $usersMenus = [
            [
                'nama'          => 'Dashboard',
                'kode'          => 'DASHBOARD',
                'url'           => '/dashboard',
                'icon'          => 'LayoutGrid',
                'rel'           => 0,
                'urutan'        => 1,
                'permission_id' => 'Dashboard Show',
            ],
            [
                'nama'          => 'Data Desa',
                'kode'          => 'DATA-WARGA',
                'url'           => '/data-warga',
                'icon'          => 'House',
                'rel'           => 0,
                'urutan'        => 2,
                'permission_id' => '',
                'children'      => [
                    [
                        'nama'          => 'Wilayah',
                        'kode'          => 'DATA-WARGA-RWS',
                        'url'           => '/data-warga/rws',
                        'urutan'        => 1,
                        'permission_id' => 'Rws Show',
                    ],
                    [
                        'nama'          => 'RT',
                        'kode'          => 'DATA-WARGA-RTS',
                        'url'           => '/data-warga/rts',
                        'urutan'        => 2,
                        'permission_id' => 'Rts Show',
                    ],
                    [
                        'nama'          => 'Tabel Rumah',
                        'kode'          => 'DATA-WARGA-HOUSES',
                        'url'           => '/data-warga/houses',
                        'urutan'        => 3,
                        'permission_id' => 'Houses Show',
                    ],
                    [
                        'nama'          => 'Kartu Keluarga',
                        'kode'          => 'DATA-WARGA-FAMILIES',
                        'url'           => '/data-warga/families',
                        'urutan'        => 4,
                        'permission_id' => 'Families Show',
                    ],
                    [
                        'nama'          => 'Warga',
                        'kode'          => 'DATA-WARGA-RESIDENTS',
                        'url'           => '/data-warga/residents',
                        'urutan'        => 5,
                        'permission_id' => 'Residents Show',
                    ],
                ],
            ],
            [
                'nama'          => 'Data Master',
                'kode'          => 'DATA-MASTER',
                'url'           => '/data-master',
                'icon'          => 'FileStack',
                'rel'           => 0,
                'urutan'        => 11,
                'permission_id' => '',
                'children'      => [
                    [
                        'nama'          => 'Status Warga',
                        'kode'          => 'DATA-MASTER-RESIDENT-STATUS',
                        'url'           => '/data-master/resident-statuses',
                        'urutan'        => 1,
                        'permission_id' => 'Resident Status Show',
                    ],
                ],
            ],
            [
                'nama'          => 'Users',
                'kode'          => 'USERS',
                'url'           => '/users',
                'icon'          => 'Users',
                'rel'           => 0,
                'urutan'        => 12,
                'permission_id' => 'Users Show',
            ],
            [
                'nama'          => 'Menu & Permissions',
                'kode'          => 'USERS-MANAGEMENT',
                'url'           => '/menu-permissions',
                'icon'          => 'ShieldCheck',
                'rel'           => 0,
                'urutan'        => 13,
                'permission_id' => '',
                'children'      => [
                    [
                        'nama'          => 'Menu',
                        'kode'          => 'USERS-MENU',
                        'url'           => '/menu-permissions/menus',
                        'urutan'        => 1,
                        'permission_id' => 'Users Menu Show',
                    ],
                    [
                        'nama'          => 'Role',
                        'kode'          => 'USERS-ROLE',
                        'url'           => '/menu-permissions/roles',
                        'urutan'        => 2,
                        'permission_id' => 'Role Show',
                    ],
                    [
                        'nama'          => 'Permission',
                        'kode'          => 'USERS-PERMISSION',
                        'url'           => '/menu-permissions/permissions',
                        'urutan'        => 3,
                        'permission_id' => 'Permission Show',
                    ],
                    [
                        'nama'          => 'Activity Log',
                        'kode'          => 'USERS-LOG',
                        'url'           => '/menu-permissions/logs',
                        'urutan'        => 4,
                        'permission_id' => 'Activity Log Show',
                    ],
                ],
            ],
        ];

        $this->insertMenus($usersMenus);
    }

    private function insertMenus(array $menus, $parentId = 0)
    {
        foreach ($menus as $menuData) {
            if (!isset($menuData['nama'])) {
                continue;
            }

            $children = $menuData['children'] ?? null;
            unset($menuData['children']);

            // Generate kode kalau belum ada
            if (!isset($menuData['kode'])) {
                $menuData['kode'] = str_replace(' ', '-', strtoupper($menuData['nama']));
            }

            // Generate URL kalau belum ada
            if (!isset($menuData['url'])) {
                $menuData['url'] = str_replace(' ', '-', strtolower($menuData['nama']));
            }

            $menuData['rel'] = $parentId;

            // Cek permission_id
            if (isset($menuData['permission_id']) && $menuData['permission_id'] !== '') {
                if (is_string($menuData['permission_id'])) {
                    $permission                = Permission::where('name', $menuData['permission_id'])->first();
                    $menuData['permission_id'] = $permission?->id;
                }
            } elseif (!isset($menuData['permission_id']) || $menuData['permission_id'] === '') {
                $menuData['permission_id'] = null;
            }

            // Cek apakah sudah ada, kalau ada update, kalau belum insert
            $existingMenu = UsersMenu::where('kode', $menuData['kode'])->first();

            if ($existingMenu) {
                $existingMenu->update($menuData);
                $menuId = $existingMenu->id;
            } else {
                $newMenu = UsersMenu::create($menuData);
                $menuId  = $newMenu->id;
            }

            // Recursive ke children kalau ada
            if ($children) {
                $this->insertMenus($children, $menuId);
            }
        }
    }
}
