<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_held_carts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $t->unsignedBigInteger('branch_id');
            $t->unsignedBigInteger('created_by');
            $t->unsignedBigInteger('source_session_id');
            $t->string('label', 80)->nullable();
            $t->text('cart_json');
            $t->string('status', 16)->default('held');
            $t->unsignedInteger('version')->default(1);
            $t->unsignedBigInteger('consumed_sale_id')->nullable();
            $t->timestamps();
            $t->unique(['tenant_id', 'id'], 'phc_tenant_id');
            $t->index(['tenant_id', 'created_by', 'status', 'branch_id', 'id'], 'phc_own_list');
            foreach (['branch_id' => 'branches', 'created_by' => 'tenant_memberships', 'source_session_id' => 'cash_sessions', 'consumed_sale_id' => 'sales'] as $column => $table) {
                $t->foreign(['tenant_id', $column], 'phc_'.$column)->references(['tenant_id', 'id'])->on($table)->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Preserve held-cart history. Roll back the UI without deleting saved carts.');
    }
};
