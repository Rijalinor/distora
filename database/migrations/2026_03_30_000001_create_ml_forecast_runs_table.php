<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ml_forecast_runs', function (Blueprint $table) {
            $table->id();
            $table->string('context', 60);
            $table->foreignId('period_id')->nullable()->constrained('periods')->nullOnDelete();
            $table->string('branch', 40)->nullable();
            $table->string('scope_key', 180)->nullable();
            $table->string('entity_type', 40)->nullable();
            $table->string('entity_id', 120)->nullable();
            $table->string('entity_name')->nullable();
            $table->string('model', 40)->nullable();
            $table->boolean('is_ml')->default(false);
            $table->decimal('prediction', 18, 2)->nullable();
            $table->decimal('prediction_low', 18, 2)->nullable();
            $table->decimal('prediction_high', 18, 2)->nullable();
            $table->decimal('confidence', 7, 2)->nullable();
            $table->decimal('wape', 10, 4)->nullable();
            $table->decimal('mape', 10, 4)->nullable();
            $table->decimal('mae', 18, 4)->nullable();
            $table->decimal('rmse', 18, 4)->nullable();
            $table->timestamp('forecasted_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['context', 'period_id', 'branch', 'forecasted_at'], 'ml_runs_ctx_period_branch_idx');
            $table->index(['entity_type', 'entity_id'], 'ml_runs_entity_idx');
            $table->unique(
                ['context', 'period_id', 'branch', 'scope_key', 'entity_type', 'entity_id'],
                'ml_runs_dedupe_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_forecast_runs');
    }
};
