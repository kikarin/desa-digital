<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rws;

class RwsSeeder extends Seeder
{
    public function run(): void
    {
        $rws = [
            [
                'nomor_rw' => '01',
                'desa'      => 'Galuga',
                'kecamatan' => 'Cibungbulang',
                'kabupaten' => 'Bogor',
            ],
            [
                'nomor_rw' => '02',
                'desa'      => 'Galuga',
                'kecamatan' => 'Cibungbulang',
                'kabupaten' => 'Bogor',
            ],
            [
                'nomor_rw' => '03',
                'desa'      => 'Galuga',
                'kecamatan' => 'Cibungbulang',
                'kabupaten' => 'Bogor',
            ],
        ];

        foreach ($rws as $rw) {
            Rws::updateOrCreate(
                ['nomor_rw' => $rw['nomor_rw'], 'desa' => $rw['desa']],
                $rw
            );
        }
    }
}

