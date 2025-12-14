<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Jalankan migration untuk menghapus kolom satuan dari assistance_program_items
     * Karena satuan sudah ada di assistance_items (item master)
     */
    public function up(): void
    {
        Schema::table('assistance_program_items', function (Blueprint $table) {
            $table->dropColumn('satuan');
        });
    }

    /**
     * Rollback migration
     */
    public function down(): void
    {
        Schema::table('assistance_program_items', function (Blueprint $table) {
            $table->string('satuan', 50)->nullable()->after('jumlah');
        });
    }
};

