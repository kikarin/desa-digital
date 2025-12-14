<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Jalankan migration untuk membuat tabel assistance_programs
     */
    public function up(): void
    {
        Schema::create('assistance_programs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_program', 200)->comment('Nama program bantuan, contoh: BLT, BPNT');
            $table->year('tahun')->comment('Tahun program bantuan');
            $table->string('periode', 50)->nullable()->comment('Periode program, contoh: Triwulan 1, Bulan Januari');
            $table->enum('target_penerima', ['KELUARGA', 'INDIVIDU'])->default('KELUARGA')->comment('Target penerima bantuan');
            $table->enum('status', ['PROSES', 'SELESAI'])->default('PROSES')->comment('Status program');
            $table->text('keterangan')->nullable()->comment('Keterangan tambahan');
            $table->timestamps();
            $table->softDeletes();
            
            // Tracking user yang membuat, update, dan hapus
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    /**
     * Rollback migration - hapus tabel assistance_programs
     */
    public function down(): void
    {
        Schema::dropIfExists('assistance_programs');
    }
};

