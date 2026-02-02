<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE layanan_darurat MODIFY COLUMN kategori VARCHAR(50) NOT NULL");
        
        DB::statement("ALTER TABLE layanan_darurat MODIFY COLUMN kategori ENUM('bhabinkamtibmas', 'mobil_siaga', 'pemadam_kebakaran', 'kantor_desa') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE layanan_darurat MODIFY COLUMN kategori VARCHAR(50) NOT NULL");
        
        DB::table('layanan_darurat')
            ->where('kategori', 'kantor_desa')
            ->delete();
        
        DB::statement("ALTER TABLE layanan_darurat MODIFY COLUMN kategori ENUM('bhabinkamtibmas', 'mobil_siaga', 'pemadam_kebakaran') NOT NULL");
    }
};
