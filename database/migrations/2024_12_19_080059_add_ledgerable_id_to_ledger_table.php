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
        Schema::table('ledger', function (Blueprint $table) {
            $table->integer('ledgerable_id')->nullable()->after('balance_type'); 
            $table->integer('ledgerable_type')->nullable()->after('ledgerable_id'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledger', function (Blueprint $table) {
            $table->dropColumn('ledgerable_id');
            $table->dropColumn('ledgerable_type');
        });
    }
};
