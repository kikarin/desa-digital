<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ResidentStatus;

class ResidentStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'code' => 'AKTIF',
                'name' => 'Aktif',
            ],
            [
                'code' => 'PINDAH',
                'name' => 'Pindah',
            ],
            [
                'code' => 'MENINGGAL',
                'name' => 'Meninggal',
            ],
        ];

        foreach ($statuses as $status) {
            ResidentStatus::updateOrCreate(
                ['code' => $status['code']],
                $status
            );
        }
    }
}

