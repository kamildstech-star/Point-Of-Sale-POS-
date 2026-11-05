import { expenseErrors } from "../../validation/forms";
import { FormDialog } from "../../components/Modal";
import { useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import DashboardDrilldown, { drilldownQuery } from './DashboardDrilldown';
import { useAuth } from '../../auth/AuthProvider';
import { workspaceApi } from '../../api/workspace';
import { ErrorNotice, Input, SaveForm, Select } from '../../components/WorkspaceForm';
import { RemoteTable, requestKey, useResources } from './OperationUI';
export default function ExpensesPage() {
  const {
    user
  } = useAuth();
  const [search] = useSearchParams();
  const filters = drilldownQuery(search, ['document_id']);
  const [revision, setRevision] = useState(0);
  const [key, setKey] = useState(requestKey);
  const [error, setError] = useState(null);
  const can = p => user.permissions.includes(p);
  const refs = useResources(can('expenses.manage') ? ['expenses/categories', 'catalog/branches', 'cash/sessions', 'operations/payment-methods'] : [], revision);
  async function save(form) {
    const body = Object.fromEntries(form);
    ['category_id', 'branch_id', 'method_id'].forEach(k => {
      body[k] = Number(body[k]);
    });
    body.cash_session_id = body.cash_session_id ? Number(body.cash_session_id) : null;
    await workspaceApi.save('expenses', {
      ...body,
      idempotency_key: key
    });
    setRevision(r => r + 1);
    setKey(requestKey());
  }
  async function approve(row) {
    setError(null);
    try {
      if (row.status === 'draft') {
        await workspaceApi.save(`expenses/${row.id}/approve`, {});
        setRevision(r => r + 1);
      }
    } catch (failure) {
      setError(failure);
    }
  }
  return <main className="content"><p className="eyebrow">OPERATING COSTS</p><h1>Expenses</h1><ErrorNotice error={error || refs.error} /><DashboardDrilldown query={filters} clearTo="/expenses"/><RemoteTable key={filters} path={`expenses${filters ? `?${filters}` : ''}`} columns={[["description", "Description"], ["amount", "Amount"], ["expense_date", "Expense date"], ["status", "Status"], ["posted_at", "Posted at"]]} onSelect={can('expenses.approve') ? approve : undefined} revision={revision} />{can('expenses.manage') && <><FormDialog title={<>Add category</>} trigger="Add expense category"><SaveForm onSave={async form => {
          await workspaceApi.save('expenses/categories', {
            name: form.get('name')
          });
          setRevision(r => r + 1);
        }}><Input name="name" label="Category name" /></SaveForm></FormDialog><FormDialog title={<>Record draft expense</>} trigger="Record expense"><SaveForm onSave={save} label="Save for approval" validate={fields => expenseErrors(fields, refs.data['operations/payment-methods']?.data || [])}><Select name="category_id" label="Category" options={refs.data['expenses/categories']?.data || []} /><Select name="branch_id" label="Branch" options={refs.data['catalog/branches']?.data || []} /><Select name="method_id" label="Payment method" options={(refs.data['operations/payment-methods']?.data || []).filter(m => ['cash', 'card', 'bank'].includes(m.method_type))} /><Select name="cash_session_id" label="Shift (required for cash)" options={(refs.data['cash/sessions']?.data || []).map(s => ({
            id: s.id,
            name: `Shift ${s.id}`
          }))} required={false} /><Input name="amount" label="Amount" /><Input name="description" label="Description" maxLength={1000} /><Input name="expense_date" label="Expense date" type="date" /><Input name="reference" label="Confirmed external payment reference" required={false} /></SaveForm></FormDialog></>}</main>;
}
