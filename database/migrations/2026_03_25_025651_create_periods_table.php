<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');          // "Maret 2026"
            $table->integer('year');
            $table->integer('month');
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->json('summary')->nullable(); // snapshot stats saat ditutup
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periods');
    }
};
