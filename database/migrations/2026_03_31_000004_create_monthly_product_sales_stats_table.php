<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_product_sales_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('periods')->cascadeOnDelete();
            $table->string('branch_dist_id', 64)->default('');
            $table->string('principle_name')->default('');
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('qty_sold', 18, 2)->default(0);
            $table->decimal('total_net', 18, 2)->default(0);
            $table->decimal('total_disc_item', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(
                ['period_id', 'branch_dist_id', 'principle_name', 'product_id'],
                'monthly_sales_stats_unique'
            );
            $table->index(['period_id', 'branch_dist_id'], 'monthly_sales_stats_period_branch_idx');
            $table->index(['period_id', 'principle_name'], 'monthly_sales_stats_period_principle_idx');
            $table->index(['product_id', 'period_id'], 'monthly_sales_stats_product_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_product_sales_stats');
    }
};

