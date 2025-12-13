<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('rts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rw_id');
            $table->string('nomor_rt', 10);
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            
            $table->foreign('rw_id')->references('id')->on('rws')->onDelete('cascade');
            $table->unique(['rw_id', 'nomor_rt']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rts');
    }
};

