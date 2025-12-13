<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('residents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('family_id');
            $table->string('nik', 16)->unique();
            $table->string('nama', 100);
            $table->string('tempat_lahir', 100);
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['L', 'P'])->default('L');
            $table->unsignedBigInteger('status_id');
            $table->text('status_note')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            
            $table->foreign('family_id')->references('id')->on('families')->onDelete('cascade');
            $table->foreign('status_id')->references('id')->on('resident_statuses')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('residents');
    }
};

