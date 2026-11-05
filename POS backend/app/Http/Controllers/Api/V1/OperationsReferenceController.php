<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Operations\Conflict;
use App\Domain\Operations\Operation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OperationsReferenceController extends Operation
{
    private const TYPES = ['customers' => ['customers', 'customers.view', 'customers.manage'], 'suppliers' => ['suppliers', 'purchases.view', 'purchases.manage'], 'registers' => ['cash_registers', 'cash.open', 'settings.manage'], 'payment-methods' => ['payment_methods', 'payments.record', 'settings.manage']];

    private function definition(string $resource): array
    {
        return self::TYPES[$resource] ?? abort(404);
    }

    public function index(Request $request, string $resource)
    {
        [$table, $read] = $this->definition($resource);
        $this->permit($read);
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100']] + ($resource === 'registers' ? ['branch_id' => ['nullable', 'integer', $this->exists('branches')]] : []));
        $q = $this->query($table)->orderBy('id');
        if ($resource === 'registers' && ! empty($data['branch_id'])) {
            $q->where('branch_id', $data['branch_id']);
        }
        if (! empty($data['search'])) {
            $q->where(fn ($query) => $query->where('name', 'like', '%'.addcslashes($data['search'], '%_\\').'%')->when(in_array($resource, ['customers', 'suppliers']), fn ($qq) => $qq->orWhere('phone', 'like', '%'.addcslashes($data['search'], '%_\\').'%')));
        }

        return response()->json($q->paginate(50));
    }

    public function show(string $resource, int $id)
    {
        [$table, $read] = $this->definition($resource);
        $this->permit($read);

        return response()->json(['data' => $this->row($table, $id)]);
    }

    public function save(Request $request, string $resource, ?int $id = null)
    {
        [$table, , $write] = $this->definition($resource);
        $this->permit($write);
        $old = $id ? (array) $this->row($table, $id) : [];
        $common = ['name' => ['required', 'string', 'max:255'], 'active' => ['sometimes', 'boolean']];
        $rules = $common + match ($resource) {
            'customers' => ['phone' => ['nullable', 'string', 'max:40'], 'email' => ['nullable', 'email', 'max:255'], 'notes' => ['nullable', 'string', 'max:5000']],
            'suppliers' => ['phone' => ['nullable', 'string', 'max:40'], 'email' => ['nullable', 'email', 'max:255'], 'address' => ['nullable', 'string', 'max:2000'], 'payment_terms_days' => ['required', 'integer', 'between:0,365']],
            'registers' => ['branch_id' => ['required', 'integer', $this->exists('branches')], 'location_id' => ['required', 'integer', $this->exists('locations')]],
            'payment-methods' => ['code' => ['required', 'regex:/^[A-Z0-9_-]{1,40}$/D', $this->unique($table, 'code', $id)], 'method_type' => ['required', Rule::in(['cash', 'card', 'bank'])]],
        };
        $data = Validator::make(array_merge($old, $request->all()), $rules)->validate();
        $saved = $this->atomic(function () use ($resource, $table, $id, $data, $old) {
            $current = $id ? $this->row($table, $id) : null;
            if ($resource === 'payment-methods' && $current?->method_type === 'exchange') {
                throw new Conflict('The internal exchange credit method is managed by the returns workflow.');
            }
            if ($resource === 'registers' && $id && array_key_exists('active', $data) && ! $data['active']) {
                $this->assertRegisterCanDeactivate($id);
            }
            if ($resource === 'registers') {
                $loc = $this->row('locations', $data['location_id']);
                if ($loc->branch_id != $data['branch_id'] || $loc->type !== 'store') {
                    throw new Conflict('Choose a store location belonging to this branch.');
                }
                if ($id && ($old['branch_id'] != $data['branch_id'] || $old['location_id'] != $data['location_id']) && $this->query('cash_sessions')->where('register_id', $id)->exists()) {
                    throw new Conflict('A used register cannot move branches or locations. Create a new register.');
                }
            }
            if ($resource === 'payment-methods' && $id && $old['method_type'] !== $data['method_type'] && $this->query('sale_payments')->where('method_id', $id)->exists()) {
                throw new Conflict('A used payment method cannot change type.');
            }
            $saved = $id ?: $this->insert($table, $data);
            if ($id) {
                $this->update($table, $id, $data);
            } $this->audit('operations.reference.saved', $table, $saved);

            return $saved;
        });

        return response()->json(['data' => $this->row($table, $saved)], $id ? 200 : 201);
    }

    private function assertRegisterCanDeactivate(int $id): void
    {
        if ($this->query('cash_sessions')->where('register_id', $id)->where('status', 'open')->exists()) {
            throw new Conflict('Close the open register shift before deactivating this register.');
        }
    }

    public function destroy(string $resource, int $id)
    {
        [$table, , $write] = $this->definition($resource);
        $this->permit($write);
        $this->atomic(function () use ($resource, $table, $id) {
            $record = $this->row($table, $id);
            if ($resource === 'payment-methods' && $record->method_type === 'exchange') {
                throw new Conflict('The internal exchange credit method is managed by the returns workflow.');
            }
            if ($resource === 'registers') {
                $this->assertRegisterCanDeactivate($id);
            }
            if ($record->active) {
                $this->update($table, $id, ['active' => false]);
                $this->audit('operations.reference.deactivated', $table, $id);
            }
        });

        return response()->noContent();
    }
}
