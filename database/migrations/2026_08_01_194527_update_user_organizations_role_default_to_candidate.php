<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Normalize legacy placeholder role values.
        DB::table('user_organizations')
            ->where('role', 'member')
            ->update(['role' => 'candidate']);

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE user_organizations MODIFY role VARCHAR(255) NOT NULL DEFAULT 'candidate'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE user_organizations ALTER COLUMN role SET DEFAULT 'candidate'");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE user_organizations MODIFY role VARCHAR(255) NOT NULL DEFAULT 'member'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE user_organizations ALTER COLUMN role SET DEFAULT 'member'");
        }
    }
};
