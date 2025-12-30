<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pengajuan_surat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jenis_surat_id');
            $table->unsignedBigInteger('resident_id')->comment('Warga yang mengajukan');
            $table->date('tanggal_surat');
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->string('nomor_surat', 100)->nullable()->comment('Auto-generate setelah disetujui');
            $table->date('tanggal_disetujui')->nullable();
            $table->text('alasan_penolakan')->nullable();
            $table->unsignedBigInteger('admin_verifikasi_id')->nullable()->comment('Admin yang melakukan verifikasi');
            $table->text('tanda_tangan_digital')->nullable()->comment('Base64 atau file path untuk TTD digital');
            $table->string('foto_tanda_tangan', 255)->nullable()->comment('File path untuk foto TTD');
            $table->enum('tanda_tangan_type', ['digital', 'foto'])->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            
            $table->foreign('jenis_surat_id')->references('id')->on('jenis_surat')->onDelete('restrict');
            $table->foreign('resident_id')->references('id')->on('residents')->onDelete('restrict');
            $table->foreign('admin_verifikasi_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_surat');
    }
};

