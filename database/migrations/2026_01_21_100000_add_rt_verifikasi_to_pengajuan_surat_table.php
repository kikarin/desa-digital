<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pengajuan_surat', function (Blueprint $table) {
            $table->unsignedBigInteger('rt_verifikasi_id')->nullable()->after('admin_verifikasi_id')->comment('RT yang melakukan verifikasi');
            $table->timestamp('rt_verifikasi_at')->nullable()->after('rt_verifikasi_id')->comment('Waktu verifikasi RT');
            $table->text('rt_catatan')->nullable()->after('rt_verifikasi_at')->comment('Catatan dari RT');
            
            $table->foreign('rt_verifikasi_id')->references('id')->on('users')->onDelete('set null');
        });

        DB::statement("ALTER TABLE pengajuan_surat MODIFY COLUMN status ENUM('menunggu', 'diverifikasi_rt', 'disetujui', 'ditolak', 'diperbaiki') DEFAULT 'menunggu'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan_surat', function (Blueprint $table) {
            $table->dropForeign(['rt_verifikasi_id']);
            $table->dropColumn(['rt_verifikasi_id', 'rt_verifikasi_at', 'rt_catatan']);
        });

        DB::statement("ALTER TABLE pengajuan_surat MODIFY COLUMN status ENUM('menunggu', 'disetujui', 'ditolak', 'diperbaiki') DEFAULT 'menunggu'");
    }
};
