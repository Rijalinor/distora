<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ml_forecast_runs', function (Blueprint $table) {
            $table->decimal('actual_value', 18, 2)->nullable()->after('prediction_high');
            $table->decimal('error_abs', 18, 2)->nullable()->after('actual_value');
            $table->decimal('error_pct', 10, 4)->nullable()->after('error_abs');
            $table->timestamp('evaluated_at')->nullable()->after('forecasted_at');
        });
    }

    public function down(): void
    {
        Schema::table('ml_forecast_runs', function (Blueprint $table) {
            $table->dropColumn(['actual_value', 'error_abs', 'error_pct', 'evaluated_at']);
        });
    }
};
