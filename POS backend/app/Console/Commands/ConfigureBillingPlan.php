<?php

namespace App\Console\Commands;

use App\Domain\Billing\BillingService;
use App\Domain\Operations\Decimal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ConfigureBillingPlan extends Command
{
    protected $signature = 'billing:configure-plan {file : Operator-reviewed JSON plan configuration}';

    protected $description = 'Configure plan terms and limits; changed used prices require a new version code, and Checkout remains disabled until Stripe verification';

    public function handle(): int
    {
        try {
            $data = json_decode(file_get_contents($this->argument('file')), true, 512, JSON_THROW_ON_ERROR);
            $data = Validator::make($data, ['code' => ['required', 'regex:/^[a-z][a-z0-9-]{1,59}$/D', 'not_in:legacy-access'], 'name' => ['required', 'string', 'max:255'], 'prices' => ['required', 'array', 'max:10'], 'prices.*.code' => ['required', 'regex:/^[a-zA-Z0-9_-]{1,60}$/D', 'distinct'], 'prices.*.currency_code' => ['required', 'regex:/^[A-Z]{3}$/D'], 'prices.*.interval' => ['required', 'in:monthly,yearly'], 'prices.*.amount' => ['required', 'string', 'regex:/^\d{1,12}(?:\.\d{1,2})?$/D'], 'entitlements' => ['required', 'array'], 'entitlements.*.feature_code' => ['required', 'distinct', \Illuminate\Validation\Rule::in(BillingService::FEATURES)], 'entitlements.*.enabled' => ['required', 'boolean'], 'entitlements.*.limit_value' => ['nullable', 'integer', 'min:0']])->validate();
            DB::transaction(function () use ($data) {
                $plan = DB::table('plans')->where('code', $data['code'])->first();
                $id = $plan?->id ?? DB::table('plans')->insertGetId(['code' => $data['code'], 'name' => $data['name'], 'public' => true, 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('plans')->where('id', $id)->update(['name' => $data['name'], 'updated_at' => now()]);
                foreach ($data['prices'] as $p) {
                    if (Decimal::cmp($p['amount'], '0') <= 0) {
                        throw new \RuntimeException('Paid prices must be positive.');
                    }$old = DB::table('plan_prices')->where('code', $p['code'])->first();
                    if ($old && ($old->plan_id != $id || $old->currency_code !== $p['currency_code'] || $old->interval !== $p['interval'] || Decimal::cmp((string) $old->amount, $p['amount']) !== 0)) {
                        throw new \RuntimeException('Changed prices must use a new versioned price code.');
                    }DB::table('plan_prices')->insertOrIgnore($p + ['plan_id' => $id, 'checkout_enabled' => false, 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
                }foreach ($data['entitlements'] as $e) {
                    DB::table('plan_entitlements')->updateOrInsert(['plan_id' => $id, 'feature_code' => $e['feature_code']], ['enabled' => $e['enabled'], 'limit_value' => $e['limit_value'] ?? null]);
                }
            });
            $this->info('Plan configuration saved. Configure and verify each Stripe Price before enabling Checkout.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Plan configuration failed: '.class_basename($e).'. Review JSON and use new codes for changed price terms.');

            return self::FAILURE;
        }
    }
}
