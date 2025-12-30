<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aduan_masyarakat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kategori_aduan_id');
            $table->string('judul', 255);
            $table->text('detail_aduan');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('nama_lokasi', 255)->nullable();
            $table->unsignedBigInteger('kecamatan_id')->nullable();
            $table->unsignedBigInteger('desa_id')->nullable();
            $table->text('deskripsi_lokasi')->nullable();
            $table->enum('jenis_aduan', ['publik', 'private'])->default('publik');
            $table->text('alasan_melaporkan')->nullable();
            $table->enum('status', ['menunggu_verifikasi', 'selesai', 'dibatalkan'])->default('menunggu_verifikasi');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('kategori_aduan_id')->references('id')->on('mst_kategori_aduan')->onDelete('restrict');
            $table->foreign('kecamatan_id')->references('id')->on('mst_kecamatan')->onDelete('set null');
            $table->foreign('desa_id')->references('id')->on('mst_desa')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('aduan_masyarakat_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('aduan_masyarakat_id');
            $table->string('file_path');
            $table->enum('file_type', ['foto', 'video'])->default('foto');
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('aduan_masyarakat_id')->references('id')->on('aduan_masyarakat')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aduan_masyarakat_files');
        Schema::dropIfExists('aduan_masyarakat');
    }
};

