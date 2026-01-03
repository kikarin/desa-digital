<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JenisSurat;

class JenisSuratSeeder extends Seeder
{
    public function run(): void
    {
        $jenisSurat = [
            [
                'nama' => 'Surat Keterangan Usaha',
                'kode' => 'SKUS',
            ],
            [
                'nama' => 'Surat Keterangan Domisili',
                'kode' => 'SKDM',
            ],
            [
                'nama' => 'Surat Keterangan Pindah',
                'kode' => 'SKPP',
            ],
            [
                'nama' => 'Surat Keterangan Kelahiran',
                'kode' => 'SKKL',
            ],
            [
                'nama' => 'Surat Keterangan Kematian',
                'kode' => 'SKKM',
            ],
            [
                'nama' => 'Surat Keterangan Perkawinan',
                'kode' => 'SKPK',
            ],
        ];

        foreach ($jenisSurat as $jenis) {
            JenisSurat::updateOrCreate(
                ['nama' => $jenis['nama']],
                $jenis
            );
        }
    }
}

