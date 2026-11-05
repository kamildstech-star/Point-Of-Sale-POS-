import TableViewport from '../../components/ui/TableViewport';
import Icon from '../../components/Icon';
import { requireLines, selectedQuantityErrors, decimalDifference } from "../../validation/forms";
import Modal from "../../components/Modal";
import { useEffect, useState } from 'react';
import DashboardDrilldown, { drilldownQuery } from './DashboardDrilldown';
import { Link, useSearchParams } from 'react-router-dom';
import { useAuth } from '../../auth/AuthProvider';
import { workspaceApi } from '../../api/workspace';
import { ErrorNotice, FormSection, Input, SaveForm } from '../../components/WorkspaceForm';
import LookupSelect from '../../components/LookupSelect';
import { LineEditor, RemoteTable, requestKey, useResources } from './OperationUI';
export default function PurchasingPage() {
  const {
    user
  } = useAuth();
  const can = code => user.permissions.includes(code);
  const [search, setSearch] = useSearchParams();
  const tab = ['orders', 'receipts', 'returns', 'payables', 'settlements'].includes(search.get('tab')) ? search.get('tab') : 'orders';
  function setTab(next) { setSearch({ tab: next }); }
  const filters = drilldownQuery(search, ['branch_id', 'document_id', ...(tab === 'orders' ? ['status'] : [])]);
  const [revision, setRevision] = useState(0);
  const [form, setForm] = useState(null);
  const [lines, setLines] = useState([]);
  const [doc, setDoc] = useState(null);
  const [error, setError] = useState(null);
  const refs = useResources(can('purchases.manage') ? ['operations/suppliers', 'catalog/locations'] : []);
  useEffect(() => {
    let alive = true;
    setDoc(null); setError(null); setForm(null);
    const id = search.get('document_id');
    if (id && /^\d+$/.test(id) && tab !== 'settlements') {
      workspaceApi.get(`purchasing/${tab}/${id}`).then(result => { if (alive) setDoc(result.data); }).catch(failure => { if (alive) setError(failure); });
    }
    return () => { alive = false; };
  }, [tab, filters]);
  function start() {
    setForm({
      kind: 'order',
      key: requestKey()
    });
    setLines([]);
    setDoc(null);
  }
  async function load(row) {
    setForm(null);
    setError(null);
    try {
      setDoc((await workspaceApi.get(`purchasing/${tab}/${row.id}`)).data);
    } catch (failure) {
      setError(failure);
    }
  }
  async function approve() {
    setError(null);
    try {
      await workspaceApi.save(`purchasing/orders/${doc.id}/approve`, {});
      setDoc((await workspaceApi.get(`purchasing/orders/${doc.id}`)).data);
      setRevision(r => r + 1);
    } catch (failure) {
      setError(failure);
    }
  }
  async function save(fields) {
    let path, body;
    const idempotency_key = form.key;
    if (form.kind === 'order') {
      path = 'purchasing/orders';
      body = {
        idempotency_key,
        supplier_id: Number(fields.get('supplier_id')),
        location_id: Number(fields.get('location_id')),
        items: lines.map(l => ({
          variant_id: l.variant_id,
          ordered_qty: l.quantity,
          unit_cost: l.unit_cost,
          tax_rate: l.tax_rate
        }))
      };
    } else if (form.kind === 'receipt') {
      path = `purchasing/orders/${doc.id}/receipts`;
      body = {
        idempotency_key,
        items: doc.items.map(l => ({
          purchase_order_item_id: l.id,
          accepted_qty: fields.get(`accepted-${l.id}`) || '0',
          rejected_qty: fields.get(`rejected-${l.id}`) || '0'
        })).filter(l => /[1-9]/.test(l.accepted_qty + l.rejected_qty))
      };
    } else if (form.kind === 'return') {
      path = `purchasing/receipts/${doc.id}/returns`;
      body = {
        idempotency_key,
        reason: fields.get('reason'),
        items: doc.items.map(l => ({
          goods_receipt_item_id: l.id,
          quantity: fields.get(`return-${l.id}`) || '0'
        })).filter(l => /[1-9]/.test(l.quantity))
      };
    } else {
      path = `purchasing/payables/${doc.id}/settlements`;
      body = {
        idempotency_key,
        amount: fields.get('amount'),
        reference: fields.get('reference')
      };
    }
    await workspaceApi.save(path, body);
    setForm(null);
    setDoc(null);
    setRevision(r => r + 1);
  }
  const columns = tab === 'orders' ? [['po_number', 'Order'], ['supplier_name', 'Supplier'], ['location_name', 'Location'], ['status', 'Status'], ['total', 'Total']] : tab === 'payables' ? [['id', 'Payable'], ['goods_receipt_id', 'Receipt'], ['original_amount', 'Original'], ['outstanding_amount', 'Outstanding'], ['due_date', 'Due'], ['status', 'Status']] : tab === 'settlements' ? [['id', 'Settlement'], ['payable_id', 'Payable'], ['amount', 'Amount'], ['reference', 'Reference']] : [['id', 'Document'], ['location_name', 'Location'], ['total', 'Total'], ['posted_at', 'Posted at']];
  return <main className="content"><p className="eyebrow">SUPPLY & RECEIVING</p><h1>Purchasing</h1><div className="form-actions">{can('purchases.manage') && <button onClick={start}><Icon name="add" />New purchase order</button>}<Link to="/operations/suppliers">Manage suppliers</Link></div><ErrorNotice error={error || refs.error} /><div className="tabs">{['orders', 'receipts', 'returns', 'payables', 'settlements'].map(t => <button key={t} className={tab === t ? '' : 'secondary'} onClick={() => {
        setTab(t);
        setDoc(null);
        setForm(null);
      }}><Icon name="list" />{t}</button>)}</div><DashboardDrilldown query={filters} clearTo={`/purchasing?tab=${tab}`}/><RemoteTable label={`Purchasing ${tab}`} key={`${tab}:${filters}`} path={`purchasing/${tab}${filters ? `?${filters}` : ''}`} columns={columns} onSelect={tab !== 'settlements' ? load : undefined} revision={revision} />{doc && <section className="card"><h2>{tab} #{doc.id} · {doc.status || 'posted'}</h2>{doc.items && <TableViewport className="table-scroll"><table><thead><tr><th>Variant</th><th className="numeric">Ordered / accepted</th><th className="numeric">Received / returned</th><th className="numeric">Unit cost</th><th className="numeric">Line total</th></tr></thead><tbody>{doc.items.map(l => <tr key={l.id}><td>{l.sku || l.variant_id}</td><td className="numeric">{l.ordered_qty ?? l.accepted_qty ?? l.quantity}</td><td className="numeric">{l.received_qty ?? l.returned_qty ?? '—'}</td><td className="numeric">{l.unit_cost}</td><td className="numeric">{l.line_total}</td></tr>)}</tbody></table></TableViewport>}<div className="form-actions">{tab === 'orders' && doc.status === 'draft' && can('purchases.approve') && <button onClick={approve}><Icon name="check" />Approve order</button>}{tab === 'orders' && ['approved', 'partial'].includes(doc.status) && can('purchases.manage') && <button onClick={() => setForm({
          kind: 'receipt',
          key: requestKey()
        })}><Icon name="list" />Receive delivery</button>}{tab === 'receipts' && can('purchases.approve') && <button onClick={() => setForm({
          kind: 'return',
          key: requestKey()
        })}><Icon name="returns" />Return accepted goods</button>}{tab === 'payables' && can('purchases.settle') && <button onClick={() => setForm({
          kind: 'settlement',
          key: requestKey()
        })}><Icon name="billing" />Record supplier payment</button>}</div></section>}{form && <Modal key={form.key} title={<>{{
        order: 'Draft purchase order',
        receipt: 'Receive part or all of this order',
        return: 'Return goods to supplier',
        settlement: 'Record confirmed supplier settlement'
      }[form.kind]}</>} onClose={() => setForm(null)}><SaveForm onSave={save} label={form.kind === 'order' ? 'Save draft order' : 'Post document'} onCancel={() => setForm(null)} validate={fields => form.kind === 'order' ? requireLines(lines) : form.kind === 'receipt' ? selectedQuantityErrors(fields, doc.items, {
        prefix: 'accepted',
        secondPrefix: 'rejected',
        maximum: l => decimalDifference(l.ordered_qty, l.received_qty)
      }) : form.kind === 'return' ? selectedQuantityErrors(fields, doc.items, {
        prefix: 'return',
        maximum: l => decimalDifference(l.accepted_qty, l.returned_qty)
      }) : {}} rules={form.kind === 'settlement' ? {
        amount: {
          max: doc.outstanding_amount
        }
      } : {}}>{form.kind === 'order' && <><FormSection title="Order details" description="Choose the supplier and destination for this draft order."><div className="form-grid"><LookupSelect name="supplier_id" label="Supplier" endpoint="operations/suppliers" options={refs.data['operations/suppliers']?.data || []} /><LookupSelect name="location_id" label="Destination location" endpoint="catalog/locations" options={refs.data['catalog/locations']?.data || []} /></div></FormSection><FormSection title="Order items" description="Review quantities, unit costs and tax before saving the draft."><LineEditor lines={lines} setLines={setLines} purchasing /></FormSection></>}{form.kind === 'receipt' && <><p>Rejected units do not enter stock or payables and remain outstanding on the order.</p>{doc.items.map(l => <div className="operation-line" key={l.id}><strong>{l.sku || l.variant_id} · ordered {l.ordered_qty}, received {l.received_qty}</strong><Input name={`accepted-${l.id}`} label="Accepted quantity" value="0" /><Input name={`rejected-${l.id}`} label="Rejected quantity" value="0" /></div>)}</>}{form.kind === 'return' && <><Input name="reason" label="Reason for supplier return" />{doc.items.map(l => <div className="operation-line" key={l.id}><strong>{l.sku || l.variant_id} · accepted {l.accepted_qty}, already returned {l.returned_qty}</strong><Input name={`return-${l.id}`} label="Return quantity" value="0" /></div>)}</>}{form.kind === 'settlement' && <><p>Outstanding: {doc.outstanding_amount}. This records an external payment; it does not transfer funds.</p><Input name="amount" label="Amount paid" /><Input name="reference" label="Bank or supplier reference" /></>}</SaveForm></Modal>}</main>;
}
