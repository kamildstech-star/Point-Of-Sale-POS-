<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $base = static function (Blueprint $t, string $key) {
            $t->id();
            $t->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $t->unique(['tenant_id', 'id'], $key.'_tenant');
            $t->timestamps();
        };
        $ref = static function (Blueprint $t, string $col, string $parent, string $key, bool $nullable = false) {
            $c = $t->unsignedBigInteger($col);
            if ($nullable) {
                $c->nullable();
            } $t->foreign(['tenant_id', $col], $key)->references(['tenant_id', 'id'])->on($parent)->restrictOnDelete();
        };
        Schema::table('sales', function (Blueprint $t) {
            $t->decimal('returned_total', 19, 4)->default(0);
        });
        Schema::table('cash_sessions', function (Blueprint $t) use ($ref) {
            $ref($t, 'closed_by', 'tenant_memberships', 'cs_closed_actor', true);
            $t->dateTime('closed_at')->nullable();
            foreach (['counted_cash', 'expected_cash', 'variance'] as $c) {
                $t->decimal($c, 19, 4)->nullable();
            } $t->text('closing_methods_json')->nullable();
        });
        Schema::create('sale_returns', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'sret');
            $ref($t, 'original_sale_id', 'sales', 'sret_sale');
            $ref($t, 'requested_by', 'tenant_memberships', 'sret_actor');
            $ref($t, 'approved_by', 'tenant_memberships', 'sret_approver', true);
            $ref($t, 'location_id', 'locations', 'sret_loc');
            $t->string('return_no', 60);
            $t->unique(['tenant_id', 'return_no'], 'sret_no');
            $t->string('kind', 20)->default('return');
            $t->string('status', 20)->default('draft');
            $t->string('reason', 1000);
            $t->decimal('total', 19, 4)->default(0);
            $t->decimal('refunded_total', 19, 4)->default(0);
            $t->dateTime('posted_at')->nullable();
            $t->dateTime('approved_at')->nullable();
        });
        Schema::create('sale_return_items', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'sri');
            $ref($t, 'sale_return_id', 'sale_returns', 'sri_ret');
            $ref($t, 'sale_item_id', 'sale_items', 'sri_sale_item');
            $t->decimal('qty', 18, 4);
            $t->string('disposition', 20);
            foreach (['gross_amount', 'discount_amount', 'tax_amount', 'credit_amount'] as $c) {
                $t->decimal($c, 19, 4)->default(0);
            } $t->unique(['tenant_id', 'sale_return_id', 'sale_item_id'], 'sri_line');
        });
        Schema::create('damaged_stock_records', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'dsr');
            $ref($t, 'return_item_id', 'sale_return_items', 'dsr_line');
            $ref($t, 'variant_id', 'catalog_variants', 'dsr_variant');
            $ref($t, 'location_id', 'locations', 'dsr_loc');
            $t->decimal('quantity', 18, 4);
            $t->string('status', 20)->default('quarantined');
            $t->unique(['tenant_id', 'return_item_id'], 'dsr_source');
        });
        Schema::create('refunds', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'refund');
            $ref($t, 'sale_return_id', 'sale_returns', 'refund_ret');
            $ref($t, 'original_sale_id', 'sales', 'refund_sale');
            $ref($t, 'method_id', 'payment_methods', 'refund_method');
            $ref($t, 'cash_session_id', 'cash_sessions', 'refund_session');
            $ref($t, 'created_by', 'tenant_memberships', 'refund_actor');
            $t->decimal('amount', 19, 4);
            $t->char('currency_code', 3);
            $t->string('method_name_snapshot');
            $t->string('method_type_snapshot', 20);
            $t->string('reference', 150)->nullable();
            $t->string('status', 20)->default('recorded');
            $t->dateTime('refunded_at');
        });
        Schema::create('exchange_links', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'exl');
            $ref($t, 'sale_return_id', 'sale_returns', 'exl_return');
            $ref($t, 'replacement_sale_id', 'sales', 'exl_sale');
            $t->decimal('credit_amount', 19, 4);
            $t->unique(['tenant_id', 'sale_return_id'], 'exl_once');
            $t->unique(['tenant_id', 'replacement_sale_id'], 'exl_replacement');
        });
        Schema::create('cash_movements', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'cm');
            $ref($t, 'cash_session_id', 'cash_sessions', 'cm_session');
            $ref($t, 'actor_id', 'tenant_memberships', 'cm_actor');
            $t->string('movement_type', 20);
            $t->decimal('amount', 19, 4);
            $t->string('reason', 1000);
            $t->dateTime('occurred_at');
        });
        Schema::create('retail_expense_categories', function (Blueprint $t) use ($base) {
            $base($t, 'rec');
            $t->string('name');
            $t->boolean('active')->default(true);
        });
        Schema::create('retail_expenses', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'rexp');
            $ref($t, 'category_id', 'retail_expense_categories', 'rexp_cat');
            $ref($t, 'branch_id', 'branches', 'rexp_branch');
            $ref($t, 'cash_session_id', 'cash_sessions', 'rexp_session', true);
            $ref($t, 'method_id', 'payment_methods', 'rexp_method');
            $ref($t, 'created_by', 'tenant_memberships', 'rexp_actor');
            $ref($t, 'approved_by', 'tenant_memberships', 'rexp_approve', true);
            $t->decimal('amount', 19, 4);
            $t->char('currency_code', 3);
            $t->string('method_type_snapshot', 20);
            $t->string('description', 1000);
            $t->string('reference', 150)->nullable();
            $t->string('status', 20)->default('draft');
            $t->date('expense_date');
            $t->dateTime('posted_at')->nullable();
        });
        Schema::create('legacy_financial_snapshots', function (Blueprint $t) use ($base) {
            $base($t, 'lfs');
            $t->string('source_table', 50);
            $t->unsignedBigInteger('source_pk');
            $t->longText('original_json');
            $t->char('source_checksum', 64);
            $t->string('refund_status', 30)->default('unknown');
            $t->dateTime('imported_at');
            $t->unique(['tenant_id', 'source_table', 'source_pk', 'source_checksum'], 'lfs_version');
        });
        Schema::create('financial_reconciliations', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'frc');
            $ref($t, 'created_by', 'tenant_memberships', 'frc_actor');
            $t->string('kind', 40);
            $t->string('status', 30);
            $t->longText('report_json');
            $t->dateTime('captured_at');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Posted financial history must be retained.');
    }
};
