<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('targets', 'principal_name')) {
            Schema::table('targets', function (Blueprint $table) {
                $table->string('principal_name')->default('')->after('name');
            });
        }

        $periodIdx = DB::select("SHOW INDEX FROM targets WHERE Key_name = 'targets_period_id_idx'");
        if (empty($periodIdx)) {
            Schema::table('targets', function (Blueprint $table) {
                $table->index('period_id', 'targets_period_id_idx');
            });
        }

        $oldUnique = DB::select("SHOW INDEX FROM targets WHERE Key_name = 'targets_period_id_type_name_unique'");
        if (!empty($oldUnique)) {
            Schema::table('targets', function (Blueprint $table) {
                $table->dropUnique('targets_period_id_type_name_unique');
            });
        }

        $newUnique = DB::select("SHOW INDEX FROM targets WHERE Key_name = 'targets_period_type_name_principal_unique'");
        if (empty($newUnique)) {
            Schema::table('targets', function (Blueprint $table) {
                $table->unique(['period_id', 'type', 'name', 'principal_name'], 'targets_period_type_name_principal_unique');
            });
        }
    }

    public function down(): void
    {
        $newUnique = DB::select("SHOW INDEX FROM targets WHERE Key_name = 'targets_period_type_name_principal_unique'");
        if (!empty($newUnique)) {
            Schema::table('targets', function (Blueprint $table) {
                $table->dropUnique('targets_period_type_name_principal_unique');
            });
        }

        $periodIdx = DB::select("SHOW INDEX FROM targets WHERE Key_name = 'targets_period_id_idx'");
        if (!empty($periodIdx)) {
            Schema::table('targets', function (Blueprint $table) {
                $table->dropIndex('targets_period_id_idx');
            });
        }

        if (Schema::hasColumn('targets', 'principal_name')) {
            Schema::table('targets', function (Blueprint $table) {
                $table->dropColumn('principal_name');
            });
        }

        $oldUnique = DB::select("SHOW INDEX FROM targets WHERE Key_name = 'targets_period_id_type_name_unique'");
        if (empty($oldUnique)) {
            Schema::table('targets', function (Blueprint $table) {
                $table->unique(['period_id', 'type', 'name'], 'targets_period_id_type_name_unique');
            });
        }
    }
};
