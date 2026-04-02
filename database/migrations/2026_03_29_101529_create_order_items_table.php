<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('coffee_id')->constrained()->onDelete('cascade');

            $table->string('size_name'); // Small / Medium / Large
            $table->decimal('unit_price', 10, 2);
            $table->integer('quantity')->default(1);
            $table->decimal('subtotal', 10, 2);

            $table->timestamps();

            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
