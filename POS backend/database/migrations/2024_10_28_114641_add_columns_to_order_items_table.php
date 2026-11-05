<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds new columns to the order_items table.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('total')->after('quantity');
            $table->decimal('price_after_discount')->after('school_margin');
            $table->decimal('margin_after_discount')->after('price_after_discount');
        });
    }

    /**
     * Reverse the migrations.
     * Removes the added columns from the order_items table.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('total');
            $table->dropColumn('price_after_discount');
            $table->dropColumn('margin_after_discount');
        });
    }
};

