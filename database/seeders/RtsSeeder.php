<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rts;
use App\Models\Rws;

class RtsSeeder extends Seeder
{
    public function run(): void
    {
        $rws = Rws::all();

        if ($rws->isEmpty()) {
            $this->command->warn('Tidak ada data RW. Pastikan RwsSeeder sudah dijalankan terlebih dahulu.');
            return;
        }

        $rts = [];

        foreach ($rws as $rw) {
            for ($i = 1; $i <= 3; $i++) {
                $rts[] = [
                    'rw_id'     => $rw->id,
                    'nomor_rt'  => str_pad($i, 2, '0', STR_PAD_LEFT),
                    'keterangan' => null,
                ];
            }
        }

        foreach ($rts as $rt) {
            Rts::updateOrCreate(
                ['rw_id' => $rt['rw_id'], 'nomor_rt' => $rt['nomor_rt']],
                $rt
            );
        }
    }
}

