<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('periods')->cascadeOnDelete();
            $table->enum('type', ['salesman', 'principle']);
            $table->string('name'); // Name of the salesman or principle
            $table->decimal('target_amount', 20, 2); // e.g. 100000000.00
            $table->timestamps();

            // Prevent duplicate targets for same name in same period
            $table->unique(['period_id', 'type', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('targets');
    }
};
