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
        Schema::table('aduan_masyarakat', function (Blueprint $table) {
            $table->unsignedBigInteger('rt_verifikasi_id')->nullable()->after('status')->comment('RT yang melakukan verifikasi');
            $table->timestamp('rt_verifikasi_at')->nullable()->after('rt_verifikasi_id')->comment('Waktu verifikasi RT');
            $table->text('rt_catatan')->nullable()->after('rt_verifikasi_at')->comment('Catatan dari RT');
            $table->unsignedBigInteger('admin_verifikasi_id')->nullable()->after('rt_catatan')->comment('Admin yang melakukan verifikasi');
            $table->timestamp('admin_verifikasi_at')->nullable()->after('admin_verifikasi_id')->comment('Waktu verifikasi Admin');
            $table->text('admin_catatan')->nullable()->after('admin_verifikasi_at')->comment('Catatan dari Admin');
            
            $table->foreign('rt_verifikasi_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('admin_verifikasi_id')->references('id')->on('users')->onDelete('set null');
        });

        DB::statement("ALTER TABLE aduan_masyarakat MODIFY COLUMN status ENUM('menunggu_verifikasi', 'diverifikasi_rt', 'diverifikasi_admin', 'selesai', 'dibatalkan') DEFAULT 'menunggu_verifikasi'");

        Schema::create('aduan_masyarakat_layanan_darurat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('aduan_masyarakat_id');
            $table->unsignedBigInteger('layanan_darurat_id');
            $table->timestamps();

            $table->foreign('aduan_masyarakat_id')->references('id')->on('aduan_masyarakat')->onDelete('cascade');
            $table->foreign('layanan_darurat_id')->references('id')->on('layanan_darurat')->onDelete('cascade');
            
            $table->unique(['aduan_masyarakat_id', 'layanan_darurat_id'], 'aduan_layanan_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aduan_masyarakat_layanan_darurat');

        Schema::table('aduan_masyarakat', function (Blueprint $table) {
            $table->dropForeign(['rt_verifikasi_id']);
            $table->dropForeign(['admin_verifikasi_id']);
            $table->dropColumn([
                'rt_verifikasi_id',
                'rt_verifikasi_at',
                'rt_catatan',
                'admin_verifikasi_id',
                'admin_verifikasi_at',
                'admin_catatan',
            ]);
        });

        DB::statement("ALTER TABLE aduan_masyarakat MODIFY COLUMN status ENUM('menunggu_verifikasi', 'selesai', 'dibatalkan') DEFAULT 'menunggu_verifikasi'");
    }
};
