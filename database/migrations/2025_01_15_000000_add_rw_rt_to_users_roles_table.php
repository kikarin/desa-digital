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
        Schema::table('users_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('rw_id')->nullable()->after('role_id');
            $table->unsignedBigInteger('rt_id')->nullable()->after('rw_id');
            
            $table->foreign('rw_id')
                ->references('id')
                ->on('rws')
                ->onDelete('cascade');
                
            $table->foreign('rt_id')
                ->references('id')
                ->on('rts')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_roles', function (Blueprint $table) {
            $table->dropForeign(['rw_id']);
            $table->dropForeign(['rt_id']);
            $table->dropColumn(['rw_id', 'rt_id']);
        });
    }
};

