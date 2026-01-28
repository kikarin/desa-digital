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
            $table->string('kategori_proposal_nama', 255)->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan_proposal', function (Blueprint $table) {
            $table->dropColumn('kategori_proposal_nama');
        });
    }
};

