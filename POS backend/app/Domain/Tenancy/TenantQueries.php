<?php

namespace App\Domain\Tenancy;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

trait TenantQueries
{
    protected function context(): TenantContext { return app(TenantContext::class); }
    protected function query(string $table): Builder { return (new RecordScope($this->context()))->query($table); }
    protected function row(string $table, int|string $id): object { return $this->query($table)->where('id', $id)->first() ?? abort(404); }
    protected function permit(string $permission): void { Gate::authorize('tenant-permission', $permission); }
    protected function exists(string $table): mixed { return Rule::exists($table, 'id')->where(fn ($query) => $query->whereIn('id', $this->query($table)->select($table.'.id'))); }
    protected function unique(string $table, string $column, int|string|null $ignore = null): mixed { return Rule::unique($table, $column)->where('tenant_id', $this->context()->id())->ignore($ignore); }
    protected function audit(string $action, string $type, int|string|null $id, array $details = []): void { app(TenantService::class)->audit($action, $type, $id, $details); }
}
