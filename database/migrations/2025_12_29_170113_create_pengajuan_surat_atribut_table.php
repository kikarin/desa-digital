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
        Schema::create('pengajuan_surat_atribut', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pengajuan_surat_id');
            $table->unsignedBigInteger('atribut_jenis_surat_id');
            $table->text('nilai')->nullable()->comment('Nilai yang diisi warga');
            $table->json('lampiran_files')->nullable()->comment('Array path file jika ada lampiran');
            $table->timestamps();
            
            $table->foreign('pengajuan_surat_id')->references('id')->on('pengajuan_surat')->onDelete('cascade');
            $table->foreign('atribut_jenis_surat_id')->references('id')->on('atribut_jenis_surat')->onDelete('restrict');
            
            $table->unique(['pengajuan_surat_id', 'atribut_jenis_surat_id'], 'unique_pengajuan_atribut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_surat_atribut');
    }
};

