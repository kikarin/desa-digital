<?php

namespace Database\Seeders;

use App\Models\CategoryPermission;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class CategoryPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $categoryPermissions = [
            [
                'name'       => 'Users',
                'permission' => 'CRUD',
                'permission_common' => ['Users Login As'],
            ],
            [
                'name'       => 'Users Menu',
                'permission' => 'CRUD',
            ],
            [
                'name'              => 'Role',
                'permission'        => 'CRUD',
                'permission_common' => ['Role Set Permission'],
            ],
            [
                'name'       => 'Permission',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Category Permission',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Activity Log',
                'permission' => ['Activity Log Show', 'Activity Log Detail', 'Activity Log Delete'],
            ],
            [
                'name'       => 'Dashboard',
                'permission' => ['Dashboard Show'],
            ],
            [
                'name'       => 'Rws',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Rts',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Houses',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Resident Status',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Families',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Residents',
                'permission' => 'CRUD',
            ],
            [
                'name'              => 'Assistance Programs',
                'permission'        => 'CRUD',
                'permission_common' => ['Assistance Programs penyaluran'],
            ],
            [
                'name'       => 'Assistance Items',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Assistance Program Items',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Assistance Recipients',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Jenis Surat',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Atribut Jenis Surat',
                'permission' => 'CRUD',
            ],
            [
                'name'              => 'Pengajuan Surat',
                'permission'        => 'CRUD',
                'permission_common' => [
                    'Pengajuan Surat Verifikasi',
                    'Pengajuan Surat Export PDF',
                    'Pengajuan Surat Preview PDF',
                ],
            ],
            [
                'name'       => 'Pengajuan Saya',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Berita Pengumuman',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Bank Sampah',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Kategori Aduan',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Aduan Masyarakat',
                'permission' => ['Aduan Masyarakat Show', 'Aduan Masyarakat Add', 'Aduan Masyarakat Edit', 'Aduan Masyarakat Detail', 'Aduan Masyarakat Delete', 'Aduan Masyarakat Verifikasi'],
            ],
            [
                'name'       => 'Aduan Saya',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Layanan Darurat',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Kategori Proposal',
                'permission' => 'CRUD',
            ],
            [
                'name'       => 'Pengajuan Proposal',
                'permission' => 'CRUD',
                'permission_common' => [
                    'Pengajuan Proposal Verifikasi',
                    'Pengajuan Proposal Export PDF',
                    'Pengajuan Proposal Preview PDF',
                ],
            ],
            [
                'name'       => 'Pengajuan Proposal Saya',
                'permission' => 'CRUD',
            ],
        ];

        $listCrud = ['Show', 'Add', 'Edit', 'Detail', 'Delete'];

        foreach ($categoryPermissions as $category) {
            $existingCategoryPermission = CategoryPermission::firstOrCreate(
                ['name' => $category['name']],
                ['name' => $category['name']]
            );

            $listPermission = [];

            if ($category['permission'] === 'CRUD') {
                foreach ($listCrud as $action) {
                    $listPermission[] = "{$category['name']} {$action}";
                }
            } else {
                foreach ($category['permission'] as $value) {
                    $listPermission[] = $value;
                }
            }

            if (isset($category['permission_common'])) {
                foreach ($category['permission_common'] as $value) {
                    $listPermission[] = $value;
                }
            }

            foreach ($listPermission as $permissionName) {
                Permission::updateOrCreate(
                    ['name' => $permissionName],
                    [
                        'category_permission_id' => $existingCategoryPermission->id,
                        'name'                   => $permissionName,
                    ]
                );
            }
        }

        $this->command->info('CategoryPermissionSeeder table seeded!');
    }
}
