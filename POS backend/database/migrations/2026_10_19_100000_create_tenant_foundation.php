<?php

use App\Domain\Tenancy\LegacyTables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->create('tenants', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('slug', 100)->unique();
            $t->string('legacy_key', 60)->nullable()->unique();
            $t->string('status', 20)->default('active'); $t->string('timezone', 80);
            $t->char('currency_code', 3); $t->string('sku_policy', 40)->nullable();
            $t->timestamp('sku_policy_approved_at')->nullable(); $t->unsignedBigInteger('sku_policy_approved_by')->nullable();
            $t->timestamps();
        });
        $this->create('tenant_permissions', function (Blueprint $t) {
            $t->string('code', 80)->primary(); $t->string('description');
        });
        $this->create('tenant_roles', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $t->string('code', 80); $t->string('name'); $t->boolean('is_system')->default(true);
            $t->unique(['tenant_id', 'id'], 'tr_tenant_id'); $t->unique(['tenant_id', 'code'], 'tr_code'); $t->timestamps();
        });
        $this->create('tenant_role_permissions', function (Blueprint $t) {
            $t->unsignedBigInteger('tenant_id'); $t->unsignedBigInteger('role_id'); $t->string('permission_code', 80);
            $t->primary(['tenant_id', 'role_id', 'permission_code'], 'trp_pk');
            $t->foreign(['tenant_id', 'role_id'], 'trp_role')->references(['tenant_id', 'id'])->on('tenant_roles')->restrictOnDelete();
            $t->foreign('permission_code', 'trp_permission')->references('code')->on('tenant_permissions')->restrictOnDelete();
        });
        $this->create('branches', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $t->string('code', 40); $t->string('name'); $t->text('address')->nullable(); $t->string('phone', 40)->nullable();
            $t->string('status', 20)->default('active'); $t->timestamps();
            $t->unique(['tenant_id', 'code'], 'branch_code'); $t->unique(['tenant_id', 'id'], 'branch_tenant_id');
        });
        $this->create('locations', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $t->unsignedBigInteger('branch_id')->nullable(); $t->string('code', 40); $t->string('name');
            $t->string('type', 20)->default('store'); $t->string('status', 20)->default('active'); $t->timestamps();
            $t->unique(['tenant_id', 'code'], 'location_code'); $t->unique(['tenant_id', 'id'], 'location_tenant_id');
            $t->foreign(['tenant_id', 'branch_id'], 'location_branch')->references(['tenant_id', 'id'])->on('branches')->restrictOnDelete();
        });
        $this->create('tenant_memberships', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete(); $t->unsignedBigInteger('role_id');
            $t->string('status', 20)->default('active'); $t->timestamp('joined_at')->nullable(); $t->timestamps();
            $t->unique(['tenant_id', 'user_id'], 'membership_user'); $t->unique(['tenant_id', 'id'], 'membership_tenant_id');
            $t->foreign(['tenant_id', 'role_id'], 'membership_role')->references(['tenant_id', 'id'])->on('tenant_roles')->restrictOnDelete();
        });
        $this->create('audit_logs', function (Blueprint $t) {
            $t->id(); $t->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $t->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('action', 100); $t->string('subject_type', 80)->nullable(); $t->string('subject_id', 100)->nullable();
            $t->json('details_json')->nullable(); $t->timestamp('occurred_at'); $t->index(['tenant_id', 'occurred_at']);
        });
        foreach (LegacyTables::BUSINESS as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreignId('tenant_id')->nullable()->index()->constrained('tenants')->restrictOnDelete();
                });
            }
        }
    }

    private function create(string $table, Closure $definition): void
    {
        // The deployment helper verifies any empty tables left by an interrupted
        // local DDL operation against the successful rehearsal before resuming.
        if (! Schema::hasTable($table)) Schema::create($table, $definition);
    }
    public function down(): void
    {
        throw new RuntimeException('Retain tenant ownership and history. Use the documented application rollback, not a destructive schema rollback.');
    }
};
