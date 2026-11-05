<?php

use App\Domain\Tenancy\LegacyTables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tenant = DB::table('tenants')->where('legacy_key', 'best-uniform')->value('id');
        if (! $tenant) throw new RuntimeException('Run legacy:bootstrap --apply after the foundation migration, reconcile ownership, then resume migrations.');
        foreach (LegacyTables::BUSINESS as $table) {
            if (Schema::hasTable($table) && DB::table($table)->whereNull('tenant_id')->exists()) throw new RuntimeException('Unassigned legacy rows remain; rerun the verified bootstrap before enforcing ownership.');
        }
        foreach (LegacyTables::BUSINESS as $table) {
            if (! Schema::hasTable($table)) continue;
            // Legacy Blade writes remain owned by Best Uniform, regardless of
            // the SPA's active business. New tenants use the catalog_* tables.
            Schema::table($table, fn (Blueprint $t) => $t->unsignedBigInteger('tenant_id')->nullable(false)->default($tenant)->change());
        }
    }

    public function down(): void { throw new RuntimeException('Do not remove verified legacy ownership constraints during application rollback.'); }
};
