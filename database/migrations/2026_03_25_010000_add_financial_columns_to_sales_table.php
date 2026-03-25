<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('gross_price', 15, 2)->default(0)->after('price');
            $table->decimal('disc_item', 15, 2)->default(0)->after('gross_price');
            $table->decimal('disc_internal', 15, 2)->default(0)->after('disc_item');
            $table->decimal('disc_external', 15, 2)->default(0)->after('disc_internal');
            $table->decimal('disc_invoice', 15, 2)->default(0)->after('disc_external');
            $table->decimal('vat', 15, 2)->default(0)->after('total');
            $table->unsignedInteger('seq_no')->nullable()->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'gross_price',
                'disc_item',
                'disc_internal',
                'disc_external',
                'disc_invoice',
                'vat',
                'seq_no',
            ]);
        });
    }
};
