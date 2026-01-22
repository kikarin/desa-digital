<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistance_programs', function (Blueprint $table) {
            $table->date('tanggal_penyaluran')->nullable()->after('desil_max')->comment('Tanggal penyaluran program');
            $table->time('jam_mulai_pengambilan')->nullable()->after('tanggal_penyaluran')->comment('Jam mulai pengambilan (contoh: 08:00)');
            $table->time('jam_selesai_pengambilan')->nullable()->after('jam_mulai_pengambilan')->comment('Jam selesai pengambilan (contoh: 17:00)');
        });
    }

    public function down(): void
    {
        Schema::table('assistance_programs', function (Blueprint $table) {
            $table->dropColumn(['tanggal_penyaluran', 'jam_mulai_pengambilan', 'jam_selesai_pengambilan']);
        });
    }
};
