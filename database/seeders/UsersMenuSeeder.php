<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\UsersMenu;
use Illuminate\Support\Facades\DB;

class UsersMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users_menus')->truncate();
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
                    [
                        'nama'          => 'Item Bantuan',
                        'kode'          => 'DATA-MASTER-ASSISTANCE-ITEMS',
                        'url'           => '/data-master/assistance-items',
                        'urutan'        => 2,
                        'permission_id' => 'Assistance Items Show',
                    ],
                    [
                        'nama'          => 'Kategori Aduan',
                        'kode'          => 'DATA-MASTER-KATEGORI-ADUAN',
                        'url'           => '/data-master/kategori-aduan',
                        'urutan'        => 3,
                        'permission_id' => 'Kategori Aduan Show',
                    ],
                ],
            ],
            [
                'nama'          => 'Program Bantuan',
                'kode'          => 'PROGRAM-BANTUAN',
                'url'           => '/program-bantuan/program-bantuan',
                'icon'          => 'HandHeart',
                'rel'           => 0,
                'urutan'        => 3,
                'permission_id' => 'Assistance Programs Show',
            ],
            [
                'nama'          => 'Layanan Surat',
                'kode'          => 'LAYANAN-SURAT',
                'url'           => '/layanan-surat',
                'icon'          => 'FileText',
                'rel'           => 0,
                'urutan'        => 4,
                'permission_id' => '',
                'children'      => [
                    [
                        'nama'          => 'Jenis Surat',
                        'kode'          => 'LAYANAN-SURAT-JENIS-SURAT',
                        'url'           => '/layanan-surat/jenis-surat',
                        'urutan'        => 1,
                        'permission_id' => 'Jenis Surat Show',
                    ],
                    [
                        'nama'          => 'Atribut Jenis Surat',
                        'kode'          => 'LAYANAN-SURAT-ATRIBUT',
                        'url'           => '/layanan-surat/atribut-jenis-surat',
                        'urutan'        => 2,
                        'permission_id' => 'Atribut Jenis Surat Show',
                    ],
                    [
                        'nama'          => 'Pengajuan Surat',
                        'kode'          => 'LAYANAN-SURAT-PENGAJUAN',
                        'url'           => '/layanan-surat/pengajuan-surat',
                        'urutan'        => 3,
                        'permission_id' => 'Pengajuan Surat Show',
                    ],
                    [
                        'nama'          => 'Pengajuan Saya',
                        'kode'          => 'LAYANAN-SURAT-PENGAJUAN-SAYA',
                        'url'           => '/layanan-surat/pengajuan-saya',
                        'urutan'        => 4,
                        'permission_id' => 'Pengajuan Saya Show',
                    ],
                ],
            ],
            [
                'nama'          => 'Berita & Pengumuman',
                'kode'          => 'BERITA-PENGUMUMAN',
                'url'           => '/berita-pengumuman',
                'icon'          => 'Newspaper',
                'rel'           => 0,
                'urutan'        => 5,
                'permission_id' => 'Berita Pengumuman Show',
            ],
            [
                'nama'          => 'Bank Sampah',
                'kode'          => 'BANK-SAMPAH',
                'url'           => '/bank-sampah',
                'icon'          => 'Recycle',
                'rel'           => 0,
                'urutan'        => 6,
                'permission_id' => 'Bank Sampah Show',
            ],
            [
                'nama'          => 'Aduan',
                'kode'          => 'ADUAN',
                'url'           => '/aduan-masyarakat',
                'icon'          => 'AlertCircle',
                'rel'           => 0,
                'urutan'        => 7,
                'permission_id' => '',
                'children'      => [
                    [
                        'nama'          => 'Aduan Masyarakat',
                        'kode'          => 'ADUAN-MASYARAKAT',
                        'url'           => '/aduan-masyarakat',
                        'urutan'        => 1,
                        'permission_id' => 'Aduan Masyarakat Show',
                    ],
                    [
                        'nama'          => 'Aduan Saya',
                        'kode'          => 'ADUAN-SAYA',
                        'url'           => '/aduan-saya',
                        'urutan'        => 2,
                        'permission_id' => 'Aduan Saya Show',
                    ],
                ],
            ],
            [
                'nama'          => 'Layanan Darurat',
                'kode'          => 'LAYANAN-DARURAT',
                'url'           => '/layanan-darurat',
                'icon'          => 'Phone',
                'rel'           => 0,
                'urutan'        => 8,
                'permission_id' => 'Layanan Darurat Show',
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
