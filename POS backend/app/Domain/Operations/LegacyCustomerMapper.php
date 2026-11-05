<?php

namespace App\Domain\Operations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyCustomerMapper extends Operation
{
    public function run(): array
    {
        if ($this->context()->tenant()->legacy_key !== 'best-uniform' || ! $this->context()->isOwner()) {
            abort(403);
        }

        return $this->atomic(function () {
            $counts = ['mapped' => 0, 'needs_review' => 0, 'existing' => 0];
            if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'user_id')) {
                return $counts;
            }
            foreach ($this->query('orders')->orderBy('id')->get(['id', 'user_id']) as $order) {
                if ($this->query('legacy_customer_links')->where('legacy_order_id', $order->id)->exists()) {
                    $counts['existing']++;

                    continue;
                }
                $user = $order->user_id ? DB::table('users')->where('id', $order->user_id)->first() : null;
                $safe = $user ? ['id' => $user->id, 'name' => $user->name, 'phone' => $user->phone ?? null, 'email' => $user->email] : ['missing_user_id' => $order->user_id];
                $isStaff = $user && ($user->role_id !== null || DB::table('tenant_memberships')->where('user_id', $user->id)->exists());
                $customer = null;
                $fitsCustomer = $user && trim($user->name) !== '' && mb_strlen($user->name) <= 255 && mb_strlen($user->phone ?? '') <= 40 && mb_strlen($user->email ?? '') <= 255;
                if ($fitsCustomer && ! $isStaff) {
                    $customer = $this->query('customers')->where('legacy_user_id', $user->id)->value('id') ?? $this->insert('customers', ['name' => $user->name, 'phone' => $user->phone ?? null, 'email' => $user->email, 'legacy_user_id' => $user->id, 'notes' => 'Legacy retail identity. Original user and order references retained.', 'active' => true]);
                }
                $status = $customer ? 'mapped' : 'needs_review';
                $counts[$status]++;
                $this->insert('legacy_customer_links', ['customer_id' => $customer, 'legacy_order_id' => $order->id, 'legacy_user_id' => $order->user_id, 'status' => $status, 'snapshot_json' => json_encode($safe, JSON_THROW_ON_ERROR)]);
            }
            $this->audit('legacy.customers.mapped', 'tenant', $this->context()->id(), $counts);

            return $counts;
        });
    }
}
