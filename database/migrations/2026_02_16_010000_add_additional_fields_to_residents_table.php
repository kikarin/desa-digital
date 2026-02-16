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
        Schema::table('residents', function (Blueprint $table) {
            $table->tinyInteger('family_status')->nullable()->after('jenis_kelamin');
            $table->string('family_status_other', 100)->nullable()->after('family_status');

            $table->tinyInteger('status_kawin')->nullable()->after('family_status_other');

            $table->string('pendidikan', 100)->nullable()->after('status_kawin');
            $table->string('agama', 100)->nullable()->after('pendidikan');
            $table->string('pekerjaan', 100)->nullable()->after('agama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn([
                'family_status',
                'family_status_other',
                'status_kawin',
                'pendidikan',
                'agama',
                'pekerjaan',
            ]);
        });
    }
};

