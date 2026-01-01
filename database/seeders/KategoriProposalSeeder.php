<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KategoriProposal;

class KategoriProposalSeeder extends Seeder
{
    public function run(): void
    {
        $kategoris = [
            ['nama' => 'Bantuan Sosial'],
            ['nama' => 'Bantuan Pendidikan'],
            ['nama' => 'Bantuan Lingkungan'],
            ['nama' => 'Bantuan Kesehatan'],
            ['nama' => 'Bantuan Infrastruktur'],
            ['nama' => 'Bantuan Bencana Alam'],
        ];

        foreach ($kategoris as $kategori) {
            KategoriProposal::updateOrCreate(
                ['nama' => $kategori['nama']],
                $kategori
            );
        }
    }
}

