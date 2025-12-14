<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Jalankan migration untuk membuat tabel assistance_program_items
     */
    public function up(): void
    {
        Schema::create('assistance_program_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assistance_program_id')->comment('ID Program Bantuan');
            $table->unsignedBigInteger('assistance_item_id')->comment('ID Item Bantuan');
            $table->decimal('jumlah', 15, 2)->comment('Jumlah/nominal bantuan, contoh: 300000, 10');
            $table->string('satuan', 50)->comment('Satuan, contoh: Rupiah, Kg');
            $table->timestamps();
            $table->softDeletes();
            
            // Tracking user yang membuat, update, dan hapus
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            
            // Foreign keys
            $table->foreign('assistance_program_id')->references('id')->on('assistance_programs')->onDelete('cascade');
            $table->foreign('assistance_item_id')->references('id')->on('assistance_items')->onDelete('restrict');
            
            // Unique constraint: satu program tidak boleh punya item yang sama dua kali
            $table->unique(['assistance_program_id', 'assistance_item_id'], 'unique_program_item');
        });
    }

    /**
     * Rollback migration - hapus tabel assistance_program_items
     */
    public function down(): void
    {
        Schema::dropIfExists('assistance_program_items');
    }
};

