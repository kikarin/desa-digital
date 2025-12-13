<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Houses;
use App\Models\Rts;

class HousesSeeder extends Seeder
{
    public function run(): void
    {
        $rts = Rts::all();

        if ($rts->isEmpty()) {
            $this->command->warn('Tidak ada data RT. Pastikan RtsSeeder sudah dijalankan terlebih dahulu.');
            return;
        }

        $houses = [];
        $houseNumber = 1;

        foreach ($rts as $rt) {
            for ($i = 1; $i <= 5; $i++) {
                $houses[] = [
                    'rt_id'       => $rt->id,
                    'nomor_rumah' => str_pad($houseNumber++, 2, '0', STR_PAD_LEFT),
                    'jenis_rumah' => 'RUMAH_TINGGAL',
                    'keterangan'  => null,
                ];
            }
        }

        foreach ($houses as $house) {
            Houses::updateOrCreate(
                ['rt_id' => $house['rt_id'], 'nomor_rumah' => $house['nomor_rumah']],
                $house
            );
        }
    }
}

