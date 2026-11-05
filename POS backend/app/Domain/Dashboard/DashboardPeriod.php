<?php

namespace App\Domain\Dashboard;

use App\Domain\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class DashboardPeriod
{
    public function __construct(public string $from, public string $to, public string $timezone, public ?int $branchId = null, public int $page = 1, public int $perPage = 20, public int $limit = 10, public bool $compare = false, public ?int $cashSessionId = null)
    {
    }

    public static function fromRequest(Request $request, TenantContext $context): self
    {
        $data = $request->validate(['from' => ['sometimes', 'date_format:Y-m-d'], 'to' => ['sometimes', 'date_format:Y-m-d'],
            'branch_id' => ['nullable', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'between:1,10000'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'], 'limit' => ['sometimes', 'integer', 'between:1,20'], 'compare' => ['sometimes', 'boolean'],
            'cash_session_id' => ['nullable', 'integer', 'min:1'],
            'tenant_id' => ['prohibited'], 'branch_ids' => ['prohibited'], 'user_id' => ['prohibited'], 'membership_id' => ['prohibited'], 'cashier_membership_id' => ['prohibited']]);
        $zone = $context->tenant()->timezone;
        $to = $data['to'] ?? Carbon::now($zone)->toDateString();
        $from = $data['from'] ?? Carbon::parse($to, $zone)->subDays(29)->toDateString();
        Validator::make(['from' => $from, 'to' => $to], ['from' => ['date_format:Y-m-d'], 'to' => ['date_format:Y-m-d', 'after_or_equal:from']])->validate();
        if (Carbon::parse($from, $zone)->diffInDays(Carbon::parse($to, $zone)) >= 366) {
            throw ValidationException::withMessages(['to' => 'Choose at most 366 calendar days, including both dates.']);
        }
        $branch = isset($data['branch_id']) ? (int) $data['branch_id'] : null;
        if ($branch !== null) {
            abort_unless(in_array($branch, $context->allowedBranchIds(), true), 403);
        }

        return new self($from, $to, $zone, $branch, (int) ($data['page'] ?? 1), (int) ($data['per_page'] ?? 20), (int) ($data['limit'] ?? 10), $request->boolean('compare'), isset($data['cash_session_id']) ? (int) $data['cash_session_id'] : null);
    }

    public function start(): Carbon
    {
        return Carbon::parse($this->from, $this->timezone)->startOfDay()->setTimezone(config('app.timezone'));
    }

    public function end(): Carbon
    {
        return Carbon::parse($this->to, $this->timezone)->addDay()->startOfDay()->setTimezone(config('app.timezone'));
    }

    public function days(): array
    {
        $days = [];
        $day = Carbon::parse($this->from, $this->timezone)->startOfDay();
        $end = Carbon::parse($this->to, $this->timezone)->addDay()->startOfDay();
        while ($day->lt($end)) {
            $days[$day->toDateString()] = [$day->copy()->setTimezone(config('app.timezone'))->toDateTimeString(), $day->copy()->addDay()->setTimezone(config('app.timezone'))->toDateTimeString()];
            $day->addDay();
        }

        return $days;
    }

    public function previous(): self
    {
        $end = Carbon::parse($this->from, $this->timezone)->subDay();

        return new self($end->copy()->subDays(count($this->days()) - 1)->toDateString(), $end->toDateString(), $this->timezone, $this->branchId, $this->page, $this->perPage, $this->limit);
    }

    public function key(): array
    {
        return [$this->from, $this->to, $this->timezone, $this->branchId, $this->page, $this->perPage, $this->limit, $this->compare, $this->cashSessionId];
    }
}
