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
        Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

    $table->string('mode'); // livraison | emporter | surplace
    $table->string('status')->default('confirmed');

    $table->string('customer_name');
    $table->string('customer_phone')->nullable();
    $table->string('customer_address')->nullable();

    $table->text('instructions')->nullable();
    $table->string('pickup_time')->nullable();
    $table->text('note')->nullable();

    $table->decimal('total', 10, 2)->default(0);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
