<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Families;
use App\Models\Houses;

class FamiliesSeeder extends Seeder
{
    public function run(): void
    {
        $houses = Houses::all();

        if ($houses->isEmpty()) {
            $this->command->warn('Tidak ada data Houses. Pastikan HousesSeeder sudah dijalankan terlebih dahulu.');
            return;
        }

        $families = [];
        $kkNumber = 3201010101010001;

        foreach ($houses as $house) {
            $families[] = [
                'house_id' => $house->id,
                'no_kk'    => (string) $kkNumber++,
                'kepala_keluarga_id' => null,
                'status'   => 'AKTIF',
            ];
        }

        foreach ($families as $family) {
            Families::updateOrCreate(
                ['no_kk' => $family['no_kk']],
                $family
            );
        }
    }
}

