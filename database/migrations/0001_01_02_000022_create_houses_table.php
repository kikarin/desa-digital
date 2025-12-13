<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('houses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rt_id');
            $table->string('nomor_rumah', 50);
            $table->enum('jenis_rumah', ['RUMAH', 'KONTRAKAN', 'FASILITAS'])->default('RUMAH');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            
            $table->foreign('rt_id')->references('id')->on('rts')->onDelete('cascade');
            $table->unique(['rt_id', 'nomor_rumah']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('houses');
    }
};

