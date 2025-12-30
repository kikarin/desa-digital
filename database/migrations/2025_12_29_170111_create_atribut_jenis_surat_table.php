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
        Schema::create('atribut_jenis_surat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jenis_surat_id');
            $table->string('nama_atribut', 100);
            $table->enum('tipe_data', ['text', 'number', 'date', 'select', 'boolean']);
            $table->text('opsi_pilihan')->nullable()->comment('JSON atau comma separated untuk opsi jika tipe_data = select');
            $table->boolean('is_required')->default(false);
            $table->string('nama_lampiran', 100)->nullable()->comment('Nama lampiran jika atribut ini punya lampiran');
            $table->integer('minimal_file')->default(0)->comment('Minimal file yang harus diupload');
            $table->boolean('is_required_lampiran')->default(false)->comment('Apakah lampiran wajib');
            $table->integer('urutan')->default(0)->comment('Urutan tampilan atribut');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            
            $table->foreign('jenis_surat_id')->references('id')->on('jenis_surat')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atribut_jenis_surat');
    }
};

