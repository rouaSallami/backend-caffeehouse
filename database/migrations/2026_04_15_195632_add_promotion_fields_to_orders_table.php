<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal_price', 10, 2)->default(0)->after('notes');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('subtotal_price');
            $table->string('applied_promo_code')->nullable()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal_price',
                'discount_amount',
                'applied_promo_code',
            ]);
        });
    }
};