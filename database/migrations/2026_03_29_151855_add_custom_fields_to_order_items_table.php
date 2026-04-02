<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->integer('sugar')->default(0)->after('size_name');
            $table->string('container')->nullable()->after('sugar');
            $table->text('note')->nullable()->after('container');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['sugar', 'container', 'note']);
        });
    }
};