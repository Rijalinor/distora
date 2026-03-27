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
        // 1. Transactions Optimization
        Schema::table('transactions', function (Blueprint $table) {
            // Standard indexes
            $table->index('transaction_date');
            $table->index('upload_history_id');
            
            // Virtual Column for Branch filtering (MariaDB 10.4+)
            // Note: Use 'VIRTUAL' or 'PERSISTENT' depending on storage needs. 
            // VIRTUAL is enough for indexing in MariaDB.
            $table->string('dist_branch_id', 50)->virtualAs('JSON_UNQUOTE(JSON_EXTRACT(meta, "$.dist_id"))')->index();
        });

        // 2. Sales Optimization
        Schema::table('sales', function (Blueprint $table) {
            $table->index('total');
        });

        // 3. Upload Histories Optimization
        Schema::table('upload_histories', function (Blueprint $table) {
            $table->index('period_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['transaction_date']);
            $table->dropIndex(['upload_history_id']);
            $table->dropColumn('dist_branch_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['total']);
        });

        Schema::table('upload_histories', function (Blueprint $table) {
            $table->dropIndex(['period_id']);
        });
    }
};
