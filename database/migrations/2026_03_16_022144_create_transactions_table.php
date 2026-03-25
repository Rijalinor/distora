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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('upload_history_id')->nullable()->constrained('upload_histories')->nullOnDelete();
            $table->string('invoice_number', 100)->unique();
            $table->date('transaction_date');
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['outlet_id', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
