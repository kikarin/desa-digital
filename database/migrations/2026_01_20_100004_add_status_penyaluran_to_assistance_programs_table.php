<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update enum status untuk tambah PENYALURAN
        DB::statement("ALTER TABLE assistance_programs MODIFY COLUMN status ENUM('PROSES', 'PENYALURAN', 'SELESAI') DEFAULT 'PROSES' COMMENT 'Status program'");
    }

    public function down(): void
    {
        // Rollback ke enum lama
        DB::statement("ALTER TABLE assistance_programs MODIFY COLUMN status ENUM('PROSES', 'SELESAI') DEFAULT 'PROSES' COMMENT 'Status program'");
    }
};
