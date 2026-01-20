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
        Schema::table('resident_deaths', function (Blueprint $table) {
            $table->string('surat_bukti_kematian')->nullable()->after('keterangan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resident_deaths', function (Blueprint $table) {
            $table->dropColumn('surat_bukti_kematian');
        });
    }
};
