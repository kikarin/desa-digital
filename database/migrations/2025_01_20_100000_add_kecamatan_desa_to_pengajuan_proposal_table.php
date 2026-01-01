<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pengajuan_proposal', function (Blueprint $table) {
            $table->unsignedBigInteger('kecamatan_id')->nullable()->after('longitude');
            $table->unsignedBigInteger('desa_id')->nullable()->after('kecamatan_id');
            
            $table->foreign('kecamatan_id')->references('id')->on('mst_kecamatan')->onDelete('set null');
            $table->foreign('desa_id')->references('id')->on('mst_desa')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan_proposal', function (Blueprint $table) {
            $table->dropForeign(['kecamatan_id']);
            $table->dropForeign(['desa_id']);
            $table->dropColumn(['kecamatan_id', 'desa_id']);
        });
    }
};

