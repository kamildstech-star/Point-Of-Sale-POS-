<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_member_access', function (Blueprint $t) {
            $t->unsignedBigInteger('tenant_id');
            $t->unsignedBigInteger('membership_id');
            $t->string('branch_scope', 16)->default('all');
            $t->string('sales_scope', 16)->default('all');
            $t->timestamps();
            $t->primary(['tenant_id', 'membership_id'], 'tma_pk');
            $t->foreign(['tenant_id', 'membership_id'], 'tma_member')->references(['tenant_id', 'id'])->on('tenant_memberships')->restrictOnDelete();
        });
        foreach (['branches' => 'branch', 'roles' => 'role'] as $plural => $singular) {
            Schema::create('tenant_member_'.$plural, function (Blueprint $t) use ($plural, $singular) {
                $t->unsignedBigInteger('tenant_id');
                $t->unsignedBigInteger('membership_id');
                $t->unsignedBigInteger($singular.'_id');
                $t->timestamps();
                $t->primary(['tenant_id', 'membership_id', $singular.'_id'], 'tm'.$singular.'_pk');
                $t->foreign(['tenant_id', 'membership_id'], 'tm'.$singular.'_member')->references(['tenant_id', 'id'])->on('tenant_memberships')->restrictOnDelete();
                $t->foreign(['tenant_id', $singular.'_id'], 'tm'.$singular.'_parent')->references(['tenant_id', 'id'])->on($plural === 'roles' ? 'tenant_roles' : 'branches')->restrictOnDelete();
            });
        }
        Schema::create('platform_user_permissions', function (Blueprint $t) {
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->string('permission_code', 80);
            $t->timestamp('granted_at');
            $t->primary(['user_id', 'permission_code'], 'pup_pk');
        });
        Schema::create('platform_access_audits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->string('action', 20);
            $t->string('permission_code', 80);
            $t->string('operator', 100);
            $t->timestamp('occurred_at');
        });
        Schema::table('idempotency_records', function (Blueprint $t) {
            $t->unsignedBigInteger('actor_membership_id')->nullable();
            $t->char('access_fingerprint', 64)->nullable();
        });
        Schema::table('export_jobs', fn (Blueprint $t) => $t->char('access_fingerprint', 64)->nullable());
        foreach ([
            'sales' => [['tenant_id', 'branch_id', 'sold_at'], ['tenant_id', 'cashier_membership_id', 'sold_at']],
            'sale_returns' => [['tenant_id', 'status', 'posted_at']],
            'sale_payments' => [['tenant_id', 'recorded_at']], 'refunds' => [['tenant_id', 'refunded_at']],
            'retail_expenses' => [['tenant_id', 'branch_id', 'posted_at']],
            'stock_movements' => [['tenant_id', 'location_id', 'occurred_at']],
            'purchase_orders' => [['tenant_id', 'location_id', 'status']], 'cash_sessions' => [['tenant_id', 'opened_by', 'status']],
        ] as $table => $indexes) {
            foreach ($indexes as $n => $columns) {
                Schema::table($table, fn (Blueprint $t) => $t->index($columns, 'd2_'.$table.'_'.$n));
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Retain explicit access restrictions and financial history. Roll back application code without dropping access records.');
    }
};
