<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['transaction_id', 'seq_no', 'product_id'], 'sales_tx_seq_product_idx');
            $table->index(['product_id', 'transaction_id'], 'sales_product_tx_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['upload_history_id', 'id'], 'transactions_upload_id_idx');
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->index(['upload_history_id', 'branch', 'product_id'], 'stocks_upload_branch_product_idx');
        });

        Schema::table('import_logs', function (Blueprint $table) {
            $table->index(['upload_history_id', 'row_number'], 'import_logs_upload_row_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_tx_seq_product_idx');
            $table->dropIndex('sales_product_tx_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_upload_id_idx');
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropIndex('stocks_upload_branch_product_idx');
        });

        Schema::table('import_logs', function (Blueprint $table) {
            $table->dropIndex('import_logs_upload_row_idx');
        });
    }
};

