<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Jalankan migration untuk membuat tabel assistance_items
     */
    public function up(): void
    {
        Schema::create('assistance_items', function (Blueprint $table) {
            $table->id();
            $table->string('nama_item', 100)->comment('Nama item bantuan, contoh: Uang Tunai, Beras, Minyak');
            $table->enum('tipe', ['UANG', 'BARANG'])->default('BARANG')->comment('Tipe item bantuan');
            $table->string('satuan', 50)->comment('Satuan item, contoh: Rupiah, Kg, Liter');
            $table->timestamps();
            $table->softDeletes();
            
            // Tracking user yang membuat, update, dan hapus
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    /**
     * Rollback migration - hapus tabel assistance_items
     */
    public function down(): void
    {
        Schema::dropIfExists('assistance_items');
    }
};

