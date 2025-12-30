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
        Schema::create('admin_tanda_tangan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->comment('Admin/user yang memiliki TTD');
            $table->text('tanda_tangan_digital')->nullable()->comment('Base64 atau file path untuk TTD digital');
            $table->string('foto_tanda_tangan', 255)->nullable()->comment('File path untuk foto TTD');
            $table->enum('tanda_tangan_type', ['digital', 'foto']);
            $table->timestamps();
            
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->unique(['admin_id', 'tanda_tangan_type'], 'unique_admin_ttd_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_tanda_tangan');
    }
};

