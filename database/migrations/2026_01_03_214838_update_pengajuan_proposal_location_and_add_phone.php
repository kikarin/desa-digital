<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop foreign keys first if they exist
        if (Schema::hasColumn('pengajuan_proposal', 'kecamatan_id')) {
            Schema::table('pengajuan_proposal', function (Blueprint $table) {
                $table->dropForeign(['kecamatan_id']);
            });
        }
        if (Schema::hasColumn('pengajuan_proposal', 'desa_id')) {
            Schema::table('pengajuan_proposal', function (Blueprint $table) {
                $table->dropForeign(['desa_id']);
            });
        }
        
        // Drop columns kecamatan, kelurahan_desa, kecamatan_id, desa_id
        $columnsToDrop = [];
        if (Schema::hasColumn('pengajuan_proposal', 'kecamatan')) {
            $columnsToDrop[] = 'kecamatan';
        }
        if (Schema::hasColumn('pengajuan_proposal', 'kelurahan_desa')) {
            $columnsToDrop[] = 'kelurahan_desa';
        }
        if (Schema::hasColumn('pengajuan_proposal', 'kecamatan_id')) {
            $columnsToDrop[] = 'kecamatan_id';
        }
        if (Schema::hasColumn('pengajuan_proposal', 'desa_id')) {
            $columnsToDrop[] = 'desa_id';
        }
        
        if (!empty($columnsToDrop)) {
            Schema::table('pengajuan_proposal', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
        
        // Rename deskripsi_lokasi_tambahan to alamat using DB::statement
        if (Schema::hasColumn('pengajuan_proposal', 'deskripsi_lokasi_tambahan')) {
            DB::statement("ALTER TABLE pengajuan_proposal CHANGE COLUMN deskripsi_lokasi_tambahan alamat TEXT NULL");
        }
        
        // Add new columns
        Schema::table('pengajuan_proposal', function (Blueprint $table) {
            $table->string('nama_lokasi', 255)->nullable()->after('longitude');
            $table->string('nomor_telepon_pengaju', 20)->nullable()->after('resident_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename alamat back to deskripsi_lokasi_tambahan
        DB::statement("ALTER TABLE pengajuan_proposal CHANGE COLUMN alamat deskripsi_lokasi_tambahan TEXT NULL");
        
        Schema::table('pengajuan_proposal', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn(['nama_lokasi', 'nomor_telepon_pengaju']);
            
            // Add back old columns
            $table->string('kecamatan', 100)->nullable()->after('longitude');
            $table->string('kelurahan_desa', 100)->nullable()->after('kecamatan');
            $table->unsignedBigInteger('kecamatan_id')->nullable()->after('kelurahan_desa');
            $table->unsignedBigInteger('desa_id')->nullable()->after('kecamatan_id');
            
            // Add foreign keys back
            $table->foreign('kecamatan_id')->references('id')->on('mst_kecamatan')->onDelete('set null');
            $table->foreign('desa_id')->references('id')->on('mst_desa')->onDelete('set null');
        });
    }
};
