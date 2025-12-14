<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssistanceItem;

class AssistanceItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'nama_item' => 'Uang Tunai',
                'tipe'      => 'UANG',
                'satuan'    => 'Rupiah',
            ],
            [
                'nama_item' => 'Beras',
                'tipe'      => 'BARANG',
                'satuan'    => 'Kg',
            ],
            [
                'nama_item' => 'Minyak',
                'tipe'      => 'BARANG',
                'satuan'    => 'Liter',
            ],
        ];

        foreach ($items as $item) {
            AssistanceItem::updateOrCreate(
                ['nama_item' => $item['nama_item']],
                $item
            );
        }
    }
}

