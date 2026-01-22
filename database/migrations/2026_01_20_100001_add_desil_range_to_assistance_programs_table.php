<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistance_programs', function (Blueprint $table) {
            $table->tinyInteger('desil_min')->nullable()->after('target_penerima')->comment('Desil minimum target (1-10)');
            $table->tinyInteger('desil_max')->nullable()->after('desil_min')->comment('Desil maximum target (1-10)');
        });
    }

    public function down(): void
    {
        Schema::table('assistance_programs', function (Blueprint $table) {
            $table->dropColumn(['desil_min', 'desil_max']);
        });
    }
};
