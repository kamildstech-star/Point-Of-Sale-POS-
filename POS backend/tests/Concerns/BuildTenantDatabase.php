<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait BuildTenantDatabase
{
    protected int $legacyTenant;
    protected User $owner;

    protected function buildDatabase(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Schema::create('roles', function (Blueprint $t) { $t->id(); $t->string('role'); $t->text('permission')->nullable(); });
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('email')->nullable()->unique(); $t->string('password')->nullable(); $t->unsignedBigInteger('role_id')->nullable(); $t->rememberToken(); $t->timestamps(); });
        Schema::create('products', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('image')->nullable(); $t->integer('gender')->nullable(); $t->integer('season')->nullable(); $t->timestamps(); });
        Schema::create('schools', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('image')->nullable(); $t->decimal('percentage', 8, 2)->default(0); $t->timestamps(); });
        Schema::create('sizes', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('product_id'); $t->string('size'); $t->timestamps(); });
        Schema::create('stocks', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('product_id'); $t->unsignedBigInteger('school_id'); $t->unsignedBigInteger('size_id'); $t->integer('quantity'); $t->decimal('base_price', 8, 2); $t->decimal('sale_price', 8, 2); $t->string('product_code')->nullable(); $t->timestamps(); });
        DB::table('roles')->insert(['id' => 1, 'role' => 'Super Admin', 'permission' => '["13","2","5"]']);
        $this->owner = User::create(['name' => 'Legacy Owner', 'email' => 'owner@example.test', 'password' => 'test-password-long', 'role_id' => 1]);
        (require database_path('migrations/2026_10_19_100000_create_tenant_foundation.php'))->up();
        (require database_path('migrations/2026_10_19_110000_create_universal_catalog.php'))->up();
        $this->artisan('legacy:bootstrap', ['--apply' => true])->assertSuccessful();
        (require database_path('migrations/2026_10_19_120000_enforce_legacy_tenant_ownership.php'))->up();
        $this->legacyTenant = DB::table('tenants')->where('legacy_key', 'best-uniform')->value('id');
    }

    protected function selectTenant(int $tenant, ?User $user = null): void
    {
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->actingAs($user ?? $this->owner, 'web')->withSession(['active_tenant_id' => $tenant]);
    }

    protected function createCatalogProduct(string $name = 'Shirt', string $sku = 'SHIRT-1'): array
    {
        $unit = $this->postJson('/api/v1/catalog/units', ['code' => 'EA', 'name' => 'Each', 'quantity_precision' => 0])->assertCreated()->json('data.id');
        $id = $this->postJson('/api/v1/products', ['name' => $name, 'product_type' => 'simple', 'track_inventory' => true,
            'variant' => ['sku' => $sku, 'base_unit_id' => $unit, 'default_cost' => '10.2500', 'default_price' => '12.3456', 'tracking_mode' => 'none']])->assertCreated()->json('data.id');
        return [$id, $unit];
    }
}
