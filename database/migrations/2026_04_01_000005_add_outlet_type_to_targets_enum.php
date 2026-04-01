<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE targets MODIFY type ENUM('salesman', 'principle', 'outlet') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("DELETE FROM targets WHERE type = 'outlet'");
        DB::statement("ALTER TABLE targets MODIFY type ENUM('salesman', 'principle') NOT NULL");
    }
};
