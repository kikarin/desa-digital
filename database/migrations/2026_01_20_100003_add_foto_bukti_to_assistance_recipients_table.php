<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistance_recipients', function (Blueprint $table) {
            // Drop column lama
            $table->dropColumn('tanggal_penyaluran');
        });
        
        Schema::table('assistance_recipients', function (Blueprint $table) {
            // Tambah datetime dan foto bukti
            $table->datetime('tanggal_penyaluran')->nullable()->after('status')->comment('Tanggal dan waktu penyaluran (Asia/Jakarta)');
            $table->string('foto_bukti_pengambilan', 255)->nullable()->after('tanggal_penyaluran')->comment('Path foto bukti pengambilan');
            $table->boolean('absen_mandiri')->default(false)->after('foto_bukti_pengambilan')->comment('True jika user absen sendiri via PWA');
        });
    }

    public function down(): void
    {
        Schema::table('assistance_recipients', function (Blueprint $table) {
            $table->dropColumn(['tanggal_penyaluran', 'foto_bukti_pengambilan', 'absen_mandiri']);
        });
        
        Schema::table('assistance_recipients', function (Blueprint $table) {
            $table->date('tanggal_penyaluran')->nullable();
        });
    }
};
