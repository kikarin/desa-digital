<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('resident_moves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resident_id');
            $table->enum('jenis_pindah', ['INDIVIDU', 'KELUARGA'])->default('INDIVIDU');
            $table->text('alamat_tujuan');
            $table->string('desa', 100);
            $table->string('kecamatan', 100);
            $table->string('kabupaten', 100);
            $table->date('tanggal_pindah');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            
            $table->foreign('resident_id')->references('id')->on('residents')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_moves');
    }
};

