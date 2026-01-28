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
        Schema::table('rws', function (Blueprint $table) {
            $table->enum('dusun', ['Dusun 1', 'Dusun 2'])->nullable()->after('kabupaten');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rws', function (Blueprint $table) {
            $table->dropColumn('dusun');
        });
    }
};
