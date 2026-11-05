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
        Schema::create('daily_account', function (Blueprint $table) {
            $table->id();
            $table->decimal('closing_balance')->default(0);
            $table->decimal('sale')->default(0);
            $table->text('in')->nullable();
            $table->text('out')->nullable();
            $table->decimal('total_in')->default(0);
            $table->decimal('total_out')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_account');
    }
};
