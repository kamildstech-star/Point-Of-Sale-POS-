<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        $ref = static function (Blueprint $t, string $column, string $parent, string $key, bool $nullable = false) {
            $c = $t->unsignedBigInteger($column);
            if ($nullable) {
                $c->nullable();
            }
            $t->foreign(['tenant_id', $column], $key)->references(['tenant_id', 'id'])->on($parent)->restrictOnDelete();
        };
        Schema::table('tenants', function (Blueprint $t) {
            $t->dateTime('legacy_stock_frozen_at')->nullable();
        });
        Schema::table('branches', function (Blueprint $t) {
            $t->string('pos_engine', 16)->default('legacy');
            $t->boolean('allow_credit')->default(true);
            $t->string('tax_jurisdiction', 80)->nullable();
            $t->dateTime('pricing_approved_at')->nullable();
            $t->unsignedTinyInteger('currency_scale')->default(2);
        });
        Schema::create('idempotency_records', function (Blueprint $t) use ($base) {
            $base($t, 'idem');
            $t->string('operation', 50);
            $t->string('idempotency_key', 100);
            $t->char('request_hash', 64);
            $t->longText('response_json');
            $t->unique(['tenant_id', 'operation', 'idempotency_key'], 'idem_key');
        });
        Schema::create('inventory_balances', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'ib');
            $ref($t, 'location_id', 'locations', 'ib_loc');
            $ref($t, 'variant_id', 'catalog_variants', 'ib_var');
            $t->decimal('on_hand', 18, 4)->default(0);
            $t->decimal('reserved', 18, 4)->default(0);
            $t->unsignedBigInteger('version')->default(0);
            $t->unique(['tenant_id', 'location_id', 'variant_id'], 'ib_slot');
        });
        Schema::create('stock_movements', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'sm');
            $ref($t, 'location_id', 'locations', 'sm_loc');
            $ref($t, 'variant_id', 'catalog_variants', 'sm_var');
            $ref($t, 'actor_id', 'tenant_memberships', 'sm_actor');
            $t->decimal('quantity_delta', 18, 4);
            $t->decimal('unit_cost_snapshot', 19, 4)->nullable();
            $t->string('movement_type', 40);
            $t->string('reference_type', 40);
            $t->unsignedBigInteger('reference_id');
            $t->string('source_event_id', 150);
            $t->char('payload_hash', 64);
            $t->dateTime('occurred_at');
            $t->unique(['tenant_id', 'source_event_id'], 'sm_event');
            $t->index(['tenant_id', 'location_id', 'variant_id', 'id'], 'sm_history');
        });
        Schema::create('stock_reservations', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'sr');
            $ref($t, 'location_id', 'locations', 'sr_loc');
            $ref($t, 'variant_id', 'catalog_variants', 'sr_var');
            $ref($t, 'created_by', 'tenant_memberships', 'sr_actor');
            $t->decimal('quantity', 18, 4);
            $t->string('reference', 120);
            $t->dateTime('expires_at');
            $t->string('status', 20)->default('active');
        });
        foreach (['stock_adjustments' => 'sa', 'stock_counts' => 'sc'] as $name => $key) {
            Schema::create($name, function (Blueprint $t) use ($base, $ref, $key) {
                $base($t, $key);
                $ref($t, 'location_id', 'locations', $key.'_loc');
                $ref($t, 'created_by', 'tenant_memberships', $key.'_actor');
                $ref($t, 'approved_by', 'tenant_memberships', $key.'_approve', true);
                $t->string('reason', 1000);
                $t->string('status', 20)->default('draft');
                $t->dateTime('posted_at')->nullable();
            });
        }
        Schema::create('stock_adjustment_items', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'sai');
            $ref($t, 'adjustment_id', 'stock_adjustments', 'sai_doc');
            $ref($t, 'variant_id', 'catalog_variants', 'sai_var');
            $t->decimal('quantity_delta', 18, 4);
            $t->unique(['tenant_id', 'adjustment_id', 'variant_id'], 'sai_line');
        });
        Schema::create('stock_count_items', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'sci');
            $ref($t, 'count_id', 'stock_counts', 'sci_doc');
            $ref($t, 'variant_id', 'catalog_variants', 'sci_var');
            $t->decimal('expected_qty', 18, 4);
            $t->decimal('counted_qty', 18, 4);
            $t->decimal('variance_qty', 18, 4);
            $t->unsignedBigInteger('balance_version');
            $t->unique(['tenant_id', 'count_id', 'variant_id'], 'sci_line');
        });
        Schema::create('suppliers', function (Blueprint $t) use ($base) {
            $base($t, 'sup');
            $t->string('name');
            $t->string('phone', 40)->nullable();
            $t->string('email')->nullable();
            $t->text('address')->nullable();
            $t->unsignedSmallInteger('payment_terms_days')->default(0);
            $t->boolean('active')->default(true);
        });
        Schema::create('purchase_orders', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'po');
            $ref($t, 'supplier_id', 'suppliers', 'po_supplier');
            $ref($t, 'location_id', 'locations', 'po_loc');
            $ref($t, 'created_by', 'tenant_memberships', 'po_actor');
            $ref($t, 'approved_by', 'tenant_memberships', 'po_approve', true);
            $t->string('po_number', 70);
            $t->string('status', 20)->default('draft');
            $t->char('currency_code', 3);
            $t->decimal('total', 19, 4);
            $t->unique(['tenant_id', 'po_number'], 'po_number');
        });
        Schema::create('purchase_order_items', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'poi');
            $ref($t, 'purchase_order_id', 'purchase_orders', 'poi_po');
            $ref($t, 'variant_id', 'catalog_variants', 'poi_var');
            $t->decimal('ordered_qty', 18, 4);
            $t->decimal('received_qty', 18, 4)->default(0);
            $t->decimal('unit_cost', 19, 4);
            $t->decimal('tax_rate', 7, 4)->default(0);
            $t->decimal('line_total', 19, 4);
            $t->unique(['tenant_id', 'purchase_order_id', 'variant_id'], 'poi_line');
        });
        Schema::create('goods_receipts', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'gr');
            $ref($t, 'purchase_order_id', 'purchase_orders', 'gr_po');
            $ref($t, 'supplier_id', 'suppliers', 'gr_sup');
            $ref($t, 'location_id', 'locations', 'gr_loc');
            $ref($t, 'created_by', 'tenant_memberships', 'gr_actor');
            $t->string('status', 20)->default('posted');
            $t->dateTime('posted_at');
            $t->decimal('total', 19, 4);
        });
        Schema::create('goods_receipt_items', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'gri');
            $ref($t, 'goods_receipt_id', 'goods_receipts', 'gri_gr');
            $ref($t, 'purchase_order_item_id', 'purchase_order_items', 'gri_poi');
            $ref($t, 'variant_id', 'catalog_variants', 'gri_var');
            $t->decimal('accepted_qty', 18, 4);
            $t->decimal('rejected_qty', 18, 4)->default(0);
            $t->decimal('returned_qty', 18, 4)->default(0);
            $t->decimal('unit_cost', 19, 4);
            $t->decimal('tax_rate', 7, 4);
            $t->decimal('line_total', 19, 4);
            $t->unique(['tenant_id', 'goods_receipt_id', 'purchase_order_item_id'], 'gri_line');
        });
        Schema::create('supplier_payables', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'sp');
            $ref($t, 'goods_receipt_id', 'goods_receipts', 'sp_gr');
            $ref($t, 'supplier_id', 'suppliers', 'sp_sup');
            $t->decimal('original_amount', 19, 4);
            $t->decimal('outstanding_amount', 19, 4);
            $t->date('due_date');
            $t->string('status', 20)->default('open');
            $t->unique(['tenant_id', 'goods_receipt_id'], 'sp_source');
        });
        Schema::create('purchase_returns', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'pr');
            $ref($t, 'goods_receipt_id', 'goods_receipts', 'pr_gr');
            $ref($t, 'location_id', 'locations', 'pr_loc');
            $ref($t, 'created_by', 'tenant_memberships', 'pr_actor');
            $t->string('reason', 1000);
            $t->decimal('total', 19, 4);
            $t->dateTime('posted_at');
        });
        Schema::create('purchase_return_items', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'pri');
            $ref($t, 'purchase_return_id', 'purchase_returns', 'pri_pr');
            $ref($t, 'goods_receipt_item_id', 'goods_receipt_items', 'pri_gri');
            $ref($t, 'variant_id', 'catalog_variants', 'pri_var');
            $t->decimal('quantity', 18, 4);
            $t->decimal('unit_cost', 19, 4);
            $t->decimal('line_total', 19, 4);
        });
        Schema::create('supplier_settlements', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'ss');
            $ref($t, 'payable_id', 'supplier_payables', 'ss_payable');
            $ref($t, 'created_by', 'tenant_memberships', 'ss_actor');
            $t->decimal('amount', 19, 4);
            $t->string('reference', 150);
            $t->dateTime('posted_at');
        });
        Schema::create('customers', function (Blueprint $t) use ($base) {
            $base($t, 'cust');
            $t->string('name');
            $t->string('phone', 40)->nullable();
            $t->string('email')->nullable();
            $t->text('notes')->nullable();
            $t->boolean('active')->default(true);
            $t->unsignedBigInteger('legacy_user_id')->nullable();
            $t->unique(['tenant_id', 'legacy_user_id'], 'cust_legacy');
        });
        Schema::create('legacy_customer_links', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'lcl');
            $ref($t, 'customer_id', 'customers', 'lcl_customer', true);
            $t->unsignedBigInteger('legacy_order_id');
            $t->unsignedBigInteger('legacy_user_id')->nullable();
            $t->string('status', 30);
            $t->text('snapshot_json');
            $t->unique(['tenant_id', 'legacy_order_id'], 'lcl_order');
        });
        Schema::create('payment_methods', function (Blueprint $t) use ($base) {
            $base($t, 'pm');
            $t->string('code', 40);
            $t->string('name');
            $t->string('method_type', 20);
            $t->boolean('active')->default(true);
            $t->unique(['tenant_id', 'code'], 'pm_code');
        });
        Schema::create('cash_registers', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'cr');
            $ref($t, 'branch_id', 'branches', 'cr_branch');
            $ref($t, 'location_id', 'locations', 'cr_loc');
            $t->string('name');
            $t->boolean('active')->default(true);
        });
        Schema::create('cash_sessions', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'cs');
            $ref($t, 'register_id', 'cash_registers', 'cs_register');
            $ref($t, 'opened_by', 'tenant_memberships', 'cs_actor');
            $t->dateTime('opened_at');
            $t->decimal('opening_cash', 19, 4);
            $t->string('status', 20)->default('open');
            // NULL on closed sessions allows history; at most one open row per register.
            $t->unsignedBigInteger('open_register_id')->nullable();
            $t->unique(['tenant_id', 'open_register_id'], 'cs_one_open');
        });
        Schema::create('invoice_sequences', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'seq');
            $ref($t, 'branch_id', 'branches', 'seq_branch');
            $t->unsignedInteger('year');
            $t->unsignedBigInteger('next_number')->default(1);
            $t->unique(['tenant_id', 'branch_id', 'year'], 'seq_year');
        });
        Schema::create('sale_quotes', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'sq');
            $ref($t, 'created_by', 'tenant_memberships', 'sq_actor');
            $t->text('input_json');
            $t->longText('quote_json');
            $t->dateTime('expires_at');
            $t->unsignedBigInteger('consumed_sale_id')->nullable();
        });
        Schema::create('sales', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'sale');
            $ref($t, 'branch_id', 'branches', 'sale_branch');
            $ref($t, 'location_id', 'locations', 'sale_loc');
            $ref($t, 'register_id', 'cash_registers', 'sale_register');
            $ref($t, 'cash_session_id', 'cash_sessions', 'sale_session');
            $ref($t, 'customer_id', 'customers', 'sale_customer', true);
            $ref($t, 'cashier_membership_id', 'tenant_memberships', 'sale_actor');
            $t->string('invoice_no', 100);
            $t->unique(['tenant_id', 'invoice_no'], 'sale_invoice');
            $t->string('sale_status', 20)->default('completed');
            $t->string('payment_status', 20);
            $t->string('channel', 20)->default('pos');
            $t->string('fulfillment_status', 20)->default('fulfilled');
            $t->char('currency_code', 3);
            $t->unsignedTinyInteger('currency_scale');
            foreach (['subtotal', 'discount_total', 'tax_total', 'grand_total', 'paid_total', 'total_refunded'] as $col) {
                $t->decimal($col, 19, 4)->default(0);
            }
            $t->text('customer_snapshot_json')->nullable();
            $t->text('branch_snapshot_json');
            $t->dateTime('sold_at');
        });
        Schema::create('sale_items', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'si');
            $ref($t, 'sale_id', 'sales', 'si_sale');
            $ref($t, 'variant_id', 'catalog_variants', 'si_var');
            $t->string('sku_snapshot', 100);
            $t->string('name_snapshot', 600);
            $t->string('unit_snapshot', 30);
            $t->decimal('qty', 18, 4);
            $t->decimal('returned_qty', 18, 4)->default(0);
            $t->boolean('track_inventory');
            foreach (['unit_price', 'unit_cost_snapshot', 'discount_total', 'tax_total', 'line_total'] as $col) {
                $t->decimal($col, 19, 4);
            }
            $t->decimal('tax_rate', 7, 4);
            $t->string('tax_jurisdiction', 80)->nullable();
        });
        Schema::create('sale_payments', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'pay');
            $ref($t, 'sale_id', 'sales', 'pay_sale');
            $ref($t, 'method_id', 'payment_methods', 'pay_method');
            $ref($t, 'cash_session_id', 'cash_sessions', 'pay_session');
            $ref($t, 'created_by', 'tenant_memberships', 'pay_actor');
            $t->decimal('amount', 19, 4);
            $t->char('currency_code', 3);
            $t->string('method_name_snapshot');
            $t->string('method_type_snapshot', 20);
            $t->string('status', 20)->default('recorded');
            $t->string('reference', 150)->nullable();
            $t->dateTime('recorded_at');
        });
        foreach (['inventory_balances', 'stock_reservations'] as $table) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE '.$table.' ADD CONSTRAINT '.$table.'_nonnegative CHECK ('.($table === 'inventory_balances' ? 'on_hand >= reserved AND reserved >= 0' : 'quantity > 0').')');
            }
        }
        // Append-only ledger even for accidental query-builder update/delete calls.
        if (DB::getDriverName() === 'mysql') {
            DB::unprepared("CREATE TRIGGER stock_movements_no_update BEFORE UPDATE ON stock_movements FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock movements are immutable'");
            DB::unprepared("CREATE TRIGGER stock_movements_no_delete BEFORE DELETE ON stock_movements FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock movements are immutable'");
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Financial and inventory history must be preserved. Restore a verified backup instead.');
    }
};
