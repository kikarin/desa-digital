<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Residents;
use App\Models\Families;
use App\Models\ResidentStatus;

class ResidentsSeeder extends Seeder
{
    public function run(): void
    {
        $families = Families::all();

        if ($families->isEmpty()) {
            $this->command->warn('Tidak ada data Families. Pastikan FamiliesSeeder sudah dijalankan terlebih dahulu.');
            return;
        }

        $statusAktif = ResidentStatus::where('code', 'AKTIF')->first();

        if (!$statusAktif) {
            $this->command->warn('Tidak ada status AKTIF. Pastikan ResidentStatusSeeder sudah dijalankan terlebih dahulu.');
            return;
        }

        $residents = [];
        $nikNumber = 3201010101010001;

        $sampleNames = [
            ['Budi', 'Santoso', 'L'],
            ['Siti', 'Rahayu', 'P'],
            ['Ahmad', 'Hidayat', 'L'],
            ['Dewi', 'Kartika', 'P'],
            ['Rudi', 'Prasetyo', 'L'],
        ];

        foreach ($families as $index => $family) {
            $nameIndex = $index % count($sampleNames);
            $name = $sampleNames[$nameIndex];

            $residents[] = [
                'family_id'     => $family->id,
                'nik'           => (string) $nikNumber++,
                'nama'          => $name[0] . ' ' . $name[1],
                'tempat_lahir'  => 'Bogor',
                'tanggal_lahir' => date('Y-m-d', strtotime('-' . (25 + $index) . ' years')),
                'jenis_kelamin' => $name[2],
                'status_id'     => $statusAktif->id,
                'status_note'   => null,
            ];
        }

        foreach ($residents as $resident) {
            Residents::updateOrCreate(
                ['nik' => $resident['nik']],
                $resident
            );
        }

        foreach ($families as $family) {
            $firstResident = Residents::where('family_id', $family->id)->first();
            if ($firstResident) {
                $family->kepala_keluarga_id = $firstResident->id;
                $family->save();
            }
        }
    }
}

