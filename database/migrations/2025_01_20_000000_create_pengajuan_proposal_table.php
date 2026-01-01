<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pengajuan_proposal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kategori_proposal_id');
            $table->unsignedBigInteger('resident_id')->comment('Warga yang mengajukan');
            $table->string('nama_kegiatan', 255);
            $table->text('deskripsi_kegiatan');
            $table->decimal('usulan_anggaran', 15, 2)->comment('Format IDR');
            $table->json('file_pendukung')->nullable()->comment('Array file paths');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('kelurahan_desa', 100)->nullable();
            $table->text('deskripsi_lokasi_tambahan')->nullable();
            $table->string('thumbnail_foto_banner', 255)->nullable();
            $table->text('tanda_tangan_digital')->nullable()->comment('Base64 untuk TTD digital');
            $table->enum('status', ['menunggu_verifikasi', 'disetujui', 'ditolak'])->default('menunggu_verifikasi');
            $table->text('catatan_verifikasi')->nullable()->comment('Catatan dari admin saat verifikasi');
            $table->unsignedBigInteger('admin_verifikasi_id')->nullable()->comment('Admin yang melakukan verifikasi');
            $table->dateTime('tanggal_diverifikasi')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            
            $table->foreign('kategori_proposal_id')->references('id')->on('mst_kategori_proposal')->onDelete('restrict');
            $table->foreign('resident_id')->references('id')->on('residents')->onDelete('restrict');
            $table->foreign('admin_verifikasi_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_proposal');
    }
};

