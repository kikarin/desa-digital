<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('house_id');
            $table->string('no_kk', 50)->unique();
            $table->unsignedBigInteger('kepala_keluarga_id')->nullable();
            $table->enum('status', ['AKTIF', 'NON_AKTIF'])->default('AKTIF');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            
            $table->foreign('house_id')->references('id')->on('houses')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};

