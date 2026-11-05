import TableViewport from '../../components/ui/TableViewport';
import Icon from '../../components/Icon';
import LoadingState from '../../components/LoadingState';
import { selectedQuantityErrors, decimalDifference, externalReferenceErrors } from "../../validation/forms";
import Modal, { FormDialog } from "../../components/Modal";
import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { useAuth } from '../../auth/AuthProvider';
import { workspaceApi } from '../../api/workspace';
import { ErrorNotice, Input, SaveForm, Select, ValidatedControl } from '../../components/WorkspaceForm';
import { RemoteTable, requestKey, useResources } from './OperationUI';
export default function ReturnsPage() {
  const {
    returnId,
    originalId
  } = useParams();
  const navigate = useNavigate();
  const {
    user
  } = useAuth();
  const can = p => user.permissions.includes(p);
  const [revision, setRevision] = useState(0);
  const [error, setError] = useState(null);
  const [key, setKey] = useState(requestKey);
  const refs = useResources(originalId ? [`sales/${originalId}`] : returnId ? [`returns/${returnId}`, ...(can('sales.refund') ? ['cash/sessions', 'operations/payment-methods'] : [])] : [], revision);
  const sale = refs.data[`sales/${originalId}`]?.data;
  const doc = refs.data[`returns/${returnId}`]?.data;
  async function request(form) {
    const items = sale.items.map(l => ({
      sale_item_id: l.id,
      qty: form.get(`qty-${l.id}`) || '0',
      disposition: form.get(`disposition-${l.id}`)
    })).filter(l => /[1-9]/.test(l.qty));
    const result = await workspaceApi.save(`sales/${originalId}/returns`, {
      idempotency_key: key,
      reason: form.get('reason'),
      items
    });
    navigate(`/returns/${result.data.id}`);
    setKey(requestKey());
  }
  async function action(verb) {
    setError(null);
    try {
      await workspaceApi.save(`returns/${returnId}/${verb}`, {});
      setRevision(r => r + 1);
    } catch (failure) {
      setError(failure);
    }
  }
  async function refund(form) {
    await workspaceApi.save(`returns/${returnId}/refunds`, {
      idempotency_key: key,
      cash_session_id: Number(form.get('cash_session_id')),
      method_id: Number(form.get('method_id')),
      amount: form.get('amount'),
      reference: form.get('reference') || null
    });
    setKey(requestKey());
    setRevision(r => r + 1);
  }
  async function cancel(form) {
    const result = await workspaceApi.save(`sales/${originalId}/void`, {
      idempotency_key: key,
      reason: form.get('reason')
    });
    navigate(`/returns/${result.data.id}`);
  }
  if (!returnId && !originalId) return <main className="content"><p className="eyebrow">RETURNS & EXCHANGES</p><h1>Returns</h1><p>Open an original invoice from Sales to request a return. Refunds use the original discounted/taxed amounts and confirmed payment records.</p><Link to="/sales">Find original invoice</Link><RemoteTable path="returns" columns={[["return_no", "Return"], ["original_sale_id", "Sale"], ["kind", "Type"], ["status", "Status"], ["total", "Credit"], ["refunded_total", "Refunded"]]} onSelect={r => navigate(`/returns/${r.id}`)} /></main>;
  return <main className="content"><Link to="/returns">All returns</Link><h1>{sale ? `Return ${sale.invoice_no}` : doc ? `${doc.return_no} · ${doc.status}` : 'Return'}</h1><ErrorNotice error={error || refs.error} /><LoadingState busy={Boolean(refs.loading)} label="Loading return…" variant={sale || doc ? 'inline' : 'form'} />{sale && can('sales.return') && <Modal size="large" title={"Return " + sale.invoice_no} onClose={() => navigate('/sales/' + originalId)}><SaveForm onSave={request} label="Request return" validate={fields => selectedQuantityErrors(fields, sale.items, {
        prefix: 'qty',
        maximum: l => decimalDifference(l.qty, l.returned_qty || '0')
      })}><Input name="reason" label="Reason for return" />{sale.items.map(l => <div className="operation-line" key={l.id}><strong>{l.name_snapshot} · sold {l.qty}, returned {l.returned_qty}</strong><Input name={`qty-${l.id}`} label="Quantity to return" value="0" /><label>Condition<ValidatedControl><select name={`disposition-${l.id}`} aria-label={"Condition"}><option value="resellable">Resellable</option><option value="damaged">Damaged / quarantine</option></select></ValidatedControl></label></div>)}</SaveForm>{can('sales.void') && <details><summary>Cancel the entire sale</summary><p>Cancellation creates one full approved return. Existing returns prevent cancellation; refunds are recorded separately.</p><SaveForm onSave={cancel} label="Cancel entire sale"><Input name="reason" label="Cancellation reason" /></SaveForm></details>}</Modal>}{doc && <><section className="card" aria-busy={Boolean(refs.loading)}><p>{doc.reason}</p><p>Credit {doc.total} · already refunded/transferred {doc.refunded_total}</p><p>Remaining sale due: {doc.amounts.balance_due}; collected amount available for refund: {doc.amounts.refund_due}</p><TableViewport><table><thead><tr><th>Original line</th><th>Quantity</th><th>Condition</th><th>Credit</th></tr></thead><tbody>{doc.items.map(l => <tr key={l.id}><td>{l.sale_item_id}</td><td className="numeric">{l.qty}</td><td>{l.disposition}</td><td>{l.credit_amount}</td></tr>)}</tbody></table></TableViewport><div className="form-actions">{can('sales.return.approve') && doc.status === 'draft' && <button onClick={() => action('approve')}><Icon name="check" />Approve return</button>}{can('sales.return.approve') && doc.status === 'approved' && <button onClick={() => action('post')}><Icon name="returns" />Post return and credit</button>}<Link to={`/sales/${doc.original_sale_id}`}>Original receipt</Link>{doc.status === 'posted' && can('sales.create') && can('sales.refund') && <Link to={`/pos?exchange_return_id=${doc.id}`}>Choose replacement items</Link>}</div><h3>Recorded refunds / exchange transfers</h3>{doc.refunds.map(r => <p key={r.id}>{r.method_name_snapshot}: {r.amount} · {r.reference || 'Cash'}</p>)}</section>{doc.status === 'posted' && can('sales.refund') && <FormDialog title={<>Record confirmed refund</>} trigger="Record confirmed refund"><p>Credit first reduces any unpaid balance. Damaged items remain outside sellable stock. Card/bank refunds must be confirmed externally before recording.</p><SaveForm onSave={refund} label="Record refund" validate={fields => externalReferenceErrors(fields, refs.data['operations/payment-methods']?.data || [])} rules={{
          amount: {
            max: doc.amounts.refund_due
          }
        }}><Select name="cash_session_id" label="Open refund shift" options={(refs.data['cash/sessions']?.data || []).map(s => ({
            id: s.id,
            name: `Shift ${s.id} · register ${s.register_id}`
          }))} /><Select name="method_id" label="Refund method" options={(refs.data['operations/payment-methods']?.data || []).filter(m => ['cash', 'card', 'bank'].includes(m.method_type))} /><Input name="amount" label="Confirmed refund amount" /><Input name="reference" label="External refund reference" required={false} maxLength={150} /></SaveForm></FormDialog>}</>}</main>;
}
