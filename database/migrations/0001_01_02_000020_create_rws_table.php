<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Jalankan migration untuk membuat tabel rws
     */
    public function up(): void
    {
        Schema::create('rws', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_rw', 10)->comment('Nomor RW, contoh: 01');
            $table->string('desa', 100)->comment('Nama desa');
            $table->string('kecamatan', 100)->comment('Nama kecamatan');
            $table->string('kabupaten', 100)->comment('Nama kabupaten');
            $table->timestamps();
            $table->softDeletes();
            
            // Tracking user yang membuat, update, dan hapus
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    /**
     * Rollback migration - hapus tabel rws
     */
    public function down(): void
    {
        Schema::dropIfExists('rws');
    }
};

