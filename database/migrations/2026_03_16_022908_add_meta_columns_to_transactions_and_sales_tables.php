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
        Schema::table('transactions', function (Blueprint $table) {
            $table->json('meta')->nullable()->after('total');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->json('raw_data')->nullable()->after('sold_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('raw_data');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }
};
