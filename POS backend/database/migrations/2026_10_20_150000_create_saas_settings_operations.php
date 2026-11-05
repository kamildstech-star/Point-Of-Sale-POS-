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
        $ref = static function (Blueprint $t, string $c, string $parent, string $key, bool $nullable = false) {
            $v = $t->unsignedBigInteger($c);
            if ($nullable) {
                $v->nullable();
            }$t->foreign(['tenant_id', $c], $key)->references(['tenant_id', 'id'])->on($parent)->restrictOnDelete();
        };
        Schema::table('tenants', function (Blueprint $t) {
            $t->string('stripe_customer_id', 100)->nullable()->unique();
        });
        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('code', 60)->unique();
            $t->string('name');
            $t->boolean('public')->default(false);
            $t->boolean('active')->default(true);
            $t->unsignedSmallInteger('trial_days')->default(0);
            $t->timestamps();
        });
        Schema::create('plan_prices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $t->string('code', 60)->unique();
            $t->char('currency_code', 3);
            $t->string('interval', 20);
            $t->decimal('amount', 19, 4);
            $t->boolean('active')->default(true);
            $t->string('stripe_price_id', 100)->nullable()->unique();
            $t->boolean('checkout_enabled')->default(false);
            $t->timestamps();
        });
        Schema::create('plan_entitlements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $t->string('feature_code', 60);
            $t->boolean('enabled')->default(true);
            $t->unsignedBigInteger('limit_value')->nullable();
            $t->unique(['plan_id', 'feature_code'], 'pe_feature');
        });
        Schema::create('tenant_subscriptions', function (Blueprint $t) use ($base) {
            $base($t, 'tsub');
            $t->foreignId('plan_price_id')->constrained('plan_prices')->restrictOnDelete();
            $t->string('public_ref', 70)->unique();
            $t->string('provider', 40)->default('stripe');
            $t->string('status', 25)->default('active');
            $t->dateTime('trial_ends_at')->nullable();
            $t->dateTime('current_period_starts_at')->nullable();
            $t->dateTime('current_period_ends_at')->nullable();
            $t->dateTime('cancel_at')->nullable();
            $t->dateTime('last_provider_event_at')->nullable();
            $t->unsignedBigInteger('billing_version')->default(0);
            $t->string('stripe_subscription_id', 100)->nullable()->unique();
            $t->boolean('grandfathered')->default(true);
            $t->dateTime('verified_at')->nullable();
            $t->dateTime('past_due_since')->nullable();
            $t->unsignedBigInteger('sync_generation')->default(0);
            $t->unique('tenant_id', 'tsub_one');
        });
        Schema::create('saas_invoices', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'sbill');
            $ref($t, 'subscription_id', 'tenant_subscriptions', 'sai_sub');
            $t->foreignId('plan_price_id')->constrained('plan_prices')->restrictOnDelete();
            $t->string('invoice_no', 80)->unique();
            $t->string('public_ref', 70)->unique();
            $t->string('provider', 40)->default('stripe');
            $t->unsignedBigInteger('billing_version');
            $t->char('currency_code', 3);
            foreach (['subtotal', 'tax_total', 'total'] as $c) {
                $t->decimal($c, 19, 4)->default(0);
            }$t->string('status', 25)->default('pending');
            $t->dateTime('due_at');
            $t->dateTime('paid_at')->nullable();
            $t->boolean('entitlements_applied')->default(false);
            $t->string('stripe_invoice_id', 100)->nullable()->unique();
            $t->text('hosted_invoice_url')->nullable();
            $t->text('invoice_pdf')->nullable();
        });
        Schema::create('saas_invoice_payments', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'sip');
            $ref($t, 'saas_invoice_id', 'saas_invoices', 'sip_invoice');
            $t->string('provider', 40);
            $t->string('external_reference', 150);
            $t->decimal('amount', 19, 4);
            $t->char('currency_code', 3);
            $t->dateTime('paid_at');
            $t->unique(['provider', 'external_reference'], 'sip_reference');
        });
        Schema::create('billing_webhook_events', function (Blueprint $t) {
            $t->id();
            $t->string('provider', 40);
            $t->string('provider_event_id', 100);
            $t->char('payload_hash', 64);
            $t->longText('payload_json');
            $t->string('status', 25);
            $t->dateTime('processed_at')->nullable();
            $t->unsignedInteger('attempts')->default(0);
            $t->string('error_code', 120)->nullable();
            $t->timestamps();
            $t->unique(['provider', 'provider_event_id'], 'bwe_event');
        });
        Schema::create('subscription_items', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'sitem');
            $ref($t, 'subscription_id', 'tenant_subscriptions', 'sitem_sub');
            $t->string('stripe_item_id', 100)->unique();
            $t->string('stripe_price_id', 100);
            $t->unsignedInteger('quantity')->default(1);
            $t->dateTime('current_period_starts_at')->nullable();
            $t->dateTime('current_period_ends_at')->nullable();
        });
        Schema::create('billing_checkout_attempts', function (Blueprint $t) use ($base) {
            $base($t, 'bca');
            $t->foreignId('plan_price_id')->constrained('plan_prices')->restrictOnDelete();
            $t->string('request_key', 100);
            $t->string('public_ref', 70)->unique();
            $t->string('status', 25)->default('preparing');
            $t->unsignedTinyInteger('active_slot')->nullable();
            $t->string('stripe_session_id', 100)->nullable()->unique();
            $t->text('checkout_url')->nullable();
            $t->dateTime('expires_at');
            $t->unique(['tenant_id', 'request_key'], 'bca_key');
            $t->unique(['tenant_id', 'active_slot'], 'bca_active');
        });
        Schema::create('tenant_usage', function (Blueprint $t) use ($base) {
            $base($t, 'tusage');
            $t->string('feature_code', 60);
            $t->unsignedBigInteger('quantity');
            $t->dateTime('observed_at');
            $t->unique(['tenant_id', 'feature_code'], 'tusage_feature');
        });
        Schema::create('tenant_settings', function (Blueprint $t) use ($base) {
            $base($t, 'tset');
            $t->string('setting_key', 60);
            $t->text('value_json');
            $t->unique(['tenant_id', 'setting_key'], 'tset_key');
        });
        Schema::create('notification_templates', function (Blueprint $t) use ($base) {
            $base($t, 'ntpl');
            $t->string('event_code', 60);
            $t->text('body_template');
            $t->boolean('active')->default(true);
            $t->unique(['tenant_id', 'event_code'], 'ntpl_event');
        });
        Schema::create('tenant_notifications', function (Blueprint $t) use ($base) {
            $base($t, 'tnot');
            $t->string('event_code', 60);
            $t->string('event_key', 100);
            $t->text('body');
            $t->text('payload_json')->nullable();
            $t->dateTime('read_at')->nullable();
            $t->unique(['tenant_id', 'event_key'], 'tnot_key');
        });
        Schema::create('import_batches', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'imp');
            $ref($t, 'initiated_by', 'tenant_memberships', 'imp_actor');
            $t->string('type', 30);
            $t->string('storage_path');
            $t->string('status', 25);
            $t->longText('rows_json');
            $t->text('errors_json');
            $t->dateTime('posted_at')->nullable();
        });
        Schema::create('export_jobs', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'exp');
            $ref($t, 'initiated_by', 'tenant_memberships', 'exp_actor');
            $t->string('type', 30);
            $t->string('status', 25);
            $t->string('file_path');
            $t->dateTime('expires_at');
            $t->unsignedBigInteger('row_count')->default(0);
        });
        Schema::create('support_cases', function (Blueprint $t) use ($base, $ref) {
            $base($t, 'supc');
            $ref($t, 'created_by', 'tenant_memberships', 'supc_actor');
            $t->string('reference', 70)->unique();
            $t->string('subject');
            $t->text('description');
            $t->string('status', 25)->default('open');
            $t->text('resolution')->nullable();
            $t->dateTime('resolved_at')->nullable();
        });
        Schema::create('operational_runs', function (Blueprint $t) {
            $t->id();
            $t->string('operation', 40);
            $t->string('status', 25);
            $t->string('backup_id', 60)->nullable();
            $t->string('error_code', 150)->nullable();
            $t->dateTime('started_at');
            $t->dateTime('finished_at')->nullable();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Billing and audit history must be retained.');
    }
};
