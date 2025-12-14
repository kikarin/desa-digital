<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Jalankan migration untuk membuat tabel assistance_recipients
     */
    public function up(): void
    {
        Schema::create('assistance_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assistance_program_id')->comment('ID Program Bantuan');
            $table->enum('target_type', ['KELUARGA', 'INDIVIDU'])->comment('Tipe target: KELUARGA atau INDIVIDU');
            $table->unsignedBigInteger('family_id')->nullable()->comment('ID Keluarga (jika target_type = KELUARGA)');
            $table->unsignedBigInteger('resident_id')->nullable()->comment('ID Warga (jika target_type = INDIVIDU)');
            $table->unsignedBigInteger('kepala_keluarga_id')->nullable()->comment('ID Kepala Keluarga (auto dari family)');
            $table->unsignedBigInteger('penerima_lapangan_id')->nullable()->comment('ID Penerima di Lapangan (perwakilan)');
            $table->enum('status', ['PROSES', 'DATANG', 'TIDAK_DATANG'])->default('PROSES')->comment('Status penyaluran');
            $table->date('tanggal_penyaluran')->nullable()->comment('Tanggal penyaluran');
            $table->text('catatan')->nullable()->comment('Catatan');
            $table->timestamps();
            $table->softDeletes();
            
            // Tracking user yang membuat, update, dan hapus
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            
            // Foreign keys
            $table->foreign('assistance_program_id')->references('id')->on('assistance_programs')->onDelete('cascade');
            $table->foreign('family_id')->references('id')->on('families')->onDelete('restrict');
            $table->foreign('resident_id')->references('id')->on('residents')->onDelete('restrict');
            $table->foreign('kepala_keluarga_id')->references('id')->on('residents')->onDelete('restrict');
            $table->foreign('penerima_lapangan_id')->references('id')->on('residents')->onDelete('restrict');
            
            // Unique constraint: satu program tidak boleh punya penerima yang sama dua kali
            $table->unique(['assistance_program_id', 'family_id'], 'unique_program_family');
            $table->unique(['assistance_program_id', 'resident_id'], 'unique_program_resident');
        });
    }

    /**
     * Rollback migration - hapus tabel assistance_recipients
     */
    public function down(): void
    {
        Schema::dropIfExists('assistance_recipients');
    }
};

