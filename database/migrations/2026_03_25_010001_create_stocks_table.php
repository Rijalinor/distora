<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_history_id')->constrained('upload_histories')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('branch', 30)->comment('e.g. bjm, brb, btl, ampah');
            $table->string('principle_code', 30)->nullable();
            $table->string('principle_name')->nullable();
            $table->string('warehouse_code', 30)->nullable();
            $table->string('warehouse_name')->nullable();
            $table->string('location_code', 30)->nullable();
            $table->string('location_name')->nullable();
            $table->decimal('on_hand', 15, 2)->default(0);
            $table->decimal('on_sales', 15, 2)->default(0);
            $table->decimal('on_hand_base', 15, 2)->default(0);
            $table->decimal('on_sales_base', 15, 2)->default(0);
            $table->decimal('stock_value_on_hand', 18, 2)->default(0);
            $table->decimal('stock_value_on_sales', 18, 2)->default(0);
            $table->decimal('tonnage', 15, 4)->default(0);
            $table->decimal('was', 15, 4)->default(0)->comment('Week Average Sales');
            $table->unsignedInteger('swc')->default(0)->comment('Sales Week Coverage');
            $table->unsignedInteger('age_of_goods')->default(0);
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['upload_history_id', 'branch']);
            $table->index(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
