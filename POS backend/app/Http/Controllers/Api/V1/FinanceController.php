<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Operations\CashService;
use App\Domain\Operations\ReportService;
use App\Domain\Operations\ReturnService;
use App\Domain\Operations\WorkspaceService;
use App\Domain\Tenancy\TenantQueries;
use App\Http\Controllers\Controller;
use App\Rules\DecimalInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FinanceController extends Controller
{
    use TenantQueries;

    private function key(): array
    {
        return ['idempotency_key' => ['required', 'string', 'max:100']];
    }

    private function payments(): array
    {
        return ['payments' => ['present', 'array', 'max:10'], 'payments.*.method_id' => ['required', 'integer', 'distinct', $this->exists('payment_methods')], 'payments.*.amount' => ['required', 'string', new DecimalInput(positive: true)], 'payments.*.reference' => ['nullable', 'string', 'max:150']];
    }

    public function returns()
    {
        $this->permit('sales.view');

        return response()->json($this->query('sale_returns')->orderByDesc('id')->paginate(50));
    }

    public function returnDetail(int $id)
    {
        $this->permit('sales.view');
        $r = $this->row('sale_returns', $id);
        $r->items = $this->query('sale_return_items')->where('sale_return_id', $id)->get();
        $r->refunds = $this->query('refunds')->where('sale_return_id', $id)->get();
        $r->original_sale = $this->row('sales', $r->original_sale_id);
        $r->amounts = \App\Domain\Operations\SaleAmounts::for($r->original_sale);

        return response()->json(['data' => $r]);
    }

    public function createReturn(Request $r, int $id, ReturnService $s)
    {
        $this->permit('sales.return');
        $data = $r->validate($this->key() + ['reason' => ['required', 'string', 'max:1000'], 'items' => ['required', 'array', 'min:1', 'max:100'], 'items.*.sale_item_id' => ['required', 'integer', 'distinct', $this->exists('sale_items')], 'items.*.qty' => ['required', 'string', new DecimalInput(positive: true)], 'items.*.disposition' => ['required', Rule::in(['resellable', 'damaged'])]]);

        return response()->json(['data' => $s->create($id, $data)], 201);
    }

    public function cancel(Request $r, int $id, ReturnService $s)
    {
        $this->permit('sales.void');
        $this->permit('sales.return.approve');
        $data = $r->validate($this->key() + ['reason' => ['required', 'string', 'max:1000']]);

        return response()->json(['data' => $s->once('sale.cancel', $data['idempotency_key'], ['sale_id' => $id] + $data, function () use ($s, $id, $data) {
            $ret = $s->create($id, $data + ['kind' => 'cancel']);
            $s->approve($ret['id']);

            return $s->post($ret['id']);
        })]);
    }

    public function approveReturn(int $id, ReturnService $s)
    {
        $this->permit('sales.return.approve');

        return response()->json(['data' => $s->approve($id)]);
    }

    public function postReturn(int $id, ReturnService $s)
    {
        $this->permit('sales.return.approve');

        return response()->json(['data' => $s->post($id)]);
    }

    public function refund(Request $r, int $id, ReturnService $s)
    {
        $this->permit('sales.refund');
        $data = $r->validate($this->key() + ['cash_session_id' => ['required', 'integer', $this->exists('cash_sessions')], 'method_id' => ['required', 'integer', $this->exists('payment_methods')], 'amount' => ['required', 'string', new DecimalInput(positive: true)], 'reference' => ['nullable', 'string', 'max:150']]);

        return response()->json(['data' => $s->refund($id, $data)], 201);
    }

    public function exchange(Request $r, int $id, ReturnService $s)
    {
        foreach (['sales.return.approve', 'sales.refund', 'sales.create', 'payments.record'] as $p) {
            $this->permit($p);
        }$data = $r->validate($this->key() + ['quote_id' => ['required', 'integer', $this->exists('sale_quotes')]] + $this->payments());

        return response()->json(['data' => $s->exchange($id, $data)], 201);
    }

    public function movement(Request $r, int $id, CashService $s)
    {
        $this->permit('cash.move');
        $data = $r->validate($this->key() + ['movement_type' => ['required', Rule::in(['in', 'out'])], 'amount' => ['required', 'string', new DecimalInput(positive: true)], 'reason' => ['required', 'string', 'max:1000']]);

        return response()->json(['data' => $s->movement($id, $data)], 201);
    }

    public function close(Request $r, int $id, CashService $s)
    {
        $this->permit('cash.close');
        $data = $r->validate($this->key() + ['counted_cash' => ['required', 'string', new DecimalInput()]]);

        return response()->json(['data' => $s->close($id, $data)]);
    }

    public function categories()
    {
        $this->permit('expenses.view');

        return response()->json($this->query('retail_expense_categories')->paginate(50));
    }

    public function category(Request $r, CashService $s)
    {
        $this->permit('expenses.manage');
        $data = $r->validate(['name' => ['required', 'string', 'max:255']]);

        return $s->atomic(function () use ($data) {
            $id = DB::table('retail_expense_categories')->insertGetId($data + ['tenant_id' => $this->context()->id(), 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
            $this->audit('expense.category.created', 'expense_category', $id);

            return response()->json(['data' => ['id' => $id]], 201);
        });
    }

    public function expenses(Request $request)
    {
        $this->permit('expenses.view');

        $data = $request->validate(['document_id' => ['nullable', 'integer', 'min:1']]);
        $query = $this->query('retail_expenses');
        if (! empty($data['document_id'])) {
            $query->where('id', $data['document_id']);
        }

        return response()->json($query->orderByDesc('id')->paginate(50));
    }

    public function expense(Request $r, CashService $s)
    {
        $this->permit('expenses.manage');
        $data = $r->validate($this->key() + ['category_id' => ['required', 'integer', $this->exists('retail_expense_categories')], 'branch_id' => ['required', 'integer', $this->exists('branches')], 'cash_session_id' => ['nullable', 'integer', $this->exists('cash_sessions')], 'method_id' => ['required', 'integer', $this->exists('payment_methods')], 'amount' => ['required', 'string', new DecimalInput(positive: true)], 'description' => ['required', 'string', 'max:1000'], 'expense_date' => ['required', 'date_format:Y-m-d'], 'reference' => ['nullable', 'string', 'max:150']]);

        return response()->json(['data' => $s->expense($data)], 201);
    }

    public function approveExpense(int $id, CashService $s)
    {
        $this->permit('expenses.approve');

        return response()->json(['data' => $s->approveExpense($id)]);
    }

    public function report(Request $r, ReportService $s)
    {
        $this->permit('reports.view');
        $data = $r->validate(['from' => ['required', 'date_format:Y-m-d'], 'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'], 'branch_id' => ['nullable', 'integer', $this->exists('branches')]]);
        if (\Illuminate\Support\Carbon::parse($data['from'])->diffInDays($data['to']) > 366) {
            throw \Illuminate\Validation\ValidationException::withMessages(['to' => 'Choose a report period of 366 days or fewer.']);
        }

        return response()->json(['data' => $s->report($data)]);
    }

    public function legacy(WorkspaceService $s)
    {
        $this->permit('legacy.manage');

        return response()->json(['data' => $s->legacyReconcile()]);
    }
}
