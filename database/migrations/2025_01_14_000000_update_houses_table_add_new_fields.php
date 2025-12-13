<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $table->enum('jenis_rumah', ['RUMAH_TINGGAL', 'KONTRAKAN', 'WARUNG_TOKO_USAHA', 'FASILITAS_UMUM'])
                  ->default('RUMAH_TINGGAL')
                  ->change();
            
            $table->unsignedBigInteger('pemilik_id')->nullable()->after('keterangan');
            $table->string('nama_pemilik', 100)->nullable()->after('pemilik_id');
            $table->enum('status_hunian', ['DIHUNI', 'KOSONG'])->nullable()->after('nama_pemilik');
            $table->string('nama_usaha', 100)->nullable()->after('status_hunian');
            $table->string('nama_pengelola', 100)->nullable()->after('nama_usaha');
            $table->string('jenis_usaha', 100)->nullable()->after('nama_pengelola');
            $table->string('nama_fasilitas', 100)->nullable()->after('jenis_usaha');
            $table->enum('pengelola', ['DESA', 'RT', 'DINAS'])->nullable()->after('nama_fasilitas');
            
            $table->foreign('pemilik_id')->references('id')->on('residents')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $table->dropForeign(['pemilik_id']);
            $table->dropColumn([
                'pemilik_id',
                'nama_pemilik',
                'status_hunian',
                'nama_usaha',
                'nama_pengelola',
                'jenis_usaha',
                'nama_fasilitas',
                'pengelola',
            ]);
            
            $table->enum('jenis_rumah', ['RUMAH', 'KONTRAKAN', 'FASILITAS'])
                  ->default('RUMAH')
                  ->change();
        });
    }
};

