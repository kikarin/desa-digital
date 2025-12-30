<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KategoriAduan;

class KategoriAduanSeeder extends Seeder
{
    public function run(): void
    {
        $kategoris = [
            ['nama' => 'Fasilitas Umum'],
            ['nama' => 'Kebersihan & Lingkungan'],
            ['nama' => 'Keamanan & Ketertiban'],
            ['nama' => 'Pelayanan Publik'],
            ['nama' => 'Kesehatan'],
            ['nama' => 'Infrastruktur'],
            ['nama' => 'Sosial & Masyarakat'],
        ];

        foreach ($kategoris as $kategori) {
            KategoriAduan::updateOrCreate(
                ['nama' => $kategori['nama']],
                $kategori
            );
        }
    }
}

