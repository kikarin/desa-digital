<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('resident_moves', function (Blueprint $table) {
            $table->enum('jenis_pindah', ['INDIVIDU', 'KELUARGA'])->nullable()->default(null)->change();
            $table->text('alamat_tujuan')->nullable()->change();
            $table->string('desa', 100)->nullable()->change();
            $table->string('kecamatan', 100)->nullable()->change();
            $table->string('kabupaten', 100)->nullable()->change();
            $table->date('tanggal_pindah')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('resident_moves', function (Blueprint $table) {
            $table->enum('jenis_pindah', ['INDIVIDU', 'KELUARGA'])->nullable(false)->default('INDIVIDU')->change();
            $table->text('alamat_tujuan')->nullable(false)->change();
            $table->string('desa', 100)->nullable(false)->change();
            $table->string('kecamatan', 100)->nullable(false)->change();
            $table->string('kabupaten', 100)->nullable(false)->change();
            $table->date('tanggal_pindah')->nullable(false)->change();
        });
    }
};

