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
        
        DB::table('layanan_darurat')
            ->where('kategori', 'polsek')
            ->update(['kategori' => 'bhabinkamtibmas']);
        
        DB::table('layanan_darurat')
            ->where('kategori', 'puskesmas')
            ->update(['kategori' => 'mobil_siaga']);

        DB::table('layanan_darurat')
            ->where('kategori', 'rumah_sakit')
            ->delete();

        DB::statement("ALTER TABLE layanan_darurat MODIFY COLUMN kategori ENUM('bhabinkamtibmas', 'mobil_siaga', 'pemadam_kebakaran') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE layanan_darurat MODIFY COLUMN kategori VARCHAR(50) NOT NULL");
        
        DB::table('layanan_darurat')
            ->where('kategori', 'bhabinkamtibmas')
            ->update(['kategori' => 'polsek']);
        
        DB::table('layanan_darurat')
            ->where('kategori', 'mobil_siaga')
            ->update(['kategori' => 'puskesmas']);
        
        DB::statement("ALTER TABLE layanan_darurat MODIFY COLUMN kategori ENUM('polsek', 'puskesmas', 'pemadam_kebakaran', 'rumah_sakit') NOT NULL");
    }
};
