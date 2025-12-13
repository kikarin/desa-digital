<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Exception;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);
        try {
            DB::beginTransaction();
            $this->call(CategoryIdentitySeeder::class);
            $this->call(IdentitySeeder::class);

            $this->call(CategoryPermissionSeeder::class);
            $this->call(UsersMenuSeeder::class);
            $this->call(RoleSeeder::class);
            $this->call(UsersSeeder::class);
            $this->call(SetRolePermissionSeeder::class);
            $this->call(ResidentStatusSeeder::class);
            $this->call(RwsSeeder::class);
            $this->call(RtsSeeder::class);
            $this->call(HousesSeeder::class);
            $this->call(FamiliesSeeder::class);
            $this->call(ResidentsSeeder::class);

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
        $this->call(ImportSqlSeeder::class);

    }
}
