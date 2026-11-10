import TableViewport from '../../components/ui/TableViewport';
import Icon from '../../components/Icon';
import { requireLines } from "../../validation/forms";
import Modal from "../../components/Modal";
import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import DashboardDrilldown, { drilldownQuery } from './DashboardDrilldown';
import { useAuth } from '../../auth/AuthProvider';
import { workspaceApi } from '../../api/workspace';
import { ErrorNotice, FormSection, SaveForm, Input } from '../../components/WorkspaceForm';
import LookupSelect from '../../components/LookupSelect';
import { LineEditor, RemoteTable, requestKey, useResources } from './OperationUI';
export default function InventoryPage() {
  const {
    user
  } = useAuth();
  const [search, setSearch] = useSearchParams();
  const tab = ['balances', 'movements', 'adjustments', 'counts', 'reservations'].includes(search.get('tab')) ? search.get('tab') : 'balances';
  function setTab(next) { setSearch({ tab: next }); }
  const filters = drilldownQuery(search, ['branch_id', 'location_id', 'variant_id', 'document_id', ...(tab === 'balances' ? ['stock_state'] : [])]);
  const [revision, setRevision] = useState(0);
  const [form, setForm] = useState(null);
  const [lines, setLines] = useState([]);
  const [doc, setDoc] = useState(null);
  const [error, setError] = useState(null);
  const [report, setReport] = useState(null);
  const [busy, setBusy] = useState(false);
  const refs = useResources(user.permissions.some(permission => ['inventory.adjust', 'inventory.reserve'].includes(permission)) ? ['catalog/locations'] : [], revision);
  const can = code => user.permissions.includes(code);
  useEffect(() => {
    let alive = true;
    setDoc(null); setError(null);
    const id = search.get('document_id');
    if (id && /^\d+$/.test(id) && ['adjustments', 'counts'].includes(tab)) {
      workspaceApi.get(`inventory/${tab}/${id}`).then(result => { if (alive) setDoc(result.data); }).catch(failure => { if (alive) setError(failure); });
    }
    return () => { alive = false; };
  }, [tab, filters]);
  function start(kind) {
    setForm({
      kind,
      key: requestKey()
    });
    setLines([]);
    setDoc(null);
  }
  useEffect(() => {
    if (search.get('create') !== '1') return;
    if (can('inventory.adjust') && ['adjustments', 'counts'].includes(tab)) start(tab);
    const next = new URLSearchParams(search); next.delete('create'); setSearch(next, { replace: true });
  }, [search.toString()]);
  async function save(fields) {
    if (form.kind === 'reservations' && lines.length !== 1) throw {
      response: {
        data: {
          message: 'Choose exactly one variant for this reservation.'
        }
      }
    };
    const location_id = Number(fields.get('location_id'));
    if (form.kind === 'reservations') await workspaceApi.save('inventory/reservations', {
      idempotency_key: form.key,
      location_id,
      variant_id: lines[0]?.variant_id,
      quantity: lines[0]?.quantity,
      reference: fields.get('reference'),
      minutes: Number(fields.get('minutes'))
    });else await workspaceApi.save(`inventory/${form.kind}`, {
      idempotency_key: form.key,
      location_id,
      reason: fields.get('reason'),
      items: lines.map(line => ({
        variant_id: line.variant_id,
        [form.kind === 'counts' ? 'counted_qty' : 'quantity_delta']: line.quantity
      }))
    });
    setTab(form.kind);
    setForm(null);
    setRevision(r => r + 1);
  }
  async function select(row) {
    setError(null);
    setDoc(null);
    try {
      if (tab === 'reservations') {
        if (row.status === 'active' && can('inventory.reserve')) {
          await workspaceApi.save(`inventory/reservations/${row.id}/release`, {});
          setRevision(r => r + 1);
        }
      } else setDoc((await workspaceApi.get(`inventory/${tab}/${row.id}`)).data);
    } catch (failure) {
      setError(failure);
    }
  }
  async function post() {
    setBusy(true);
    setError(null);
    try {
      await workspaceApi.save(`inventory/${tab}/${doc.id}/post`, {});
      setDoc(null);
      setRevision(r => r + 1);
    } catch (failure) {
      setError(failure);
    } finally {
      setBusy(false);
    }
  }
  async function reconcile() {
    setError(null);
    try {
      setReport((await workspaceApi.save('inventory/reconcile', {})).data);
    } catch (failure) {
      setError(failure);
    }
  }
  const columns = ['balances', 'movements', 'reservations'].includes(tab) ? [['sku', 'SKU'], ['location_name', 'Location'], ...(tab === 'balances' ? [['on_hand', 'On hand'], ['reserved', 'Reserved'], ['version', 'Version']] : tab === 'movements' ? [['movement_type', 'Movement'], ['quantity_delta', 'Quantity change'], ['source_event_id', 'Source event'], ['occurred_at', 'Posted at']] : [['quantity', 'Held quantity'], ['reference', 'Reference'], ['expires_at', 'Expires'], ['status', 'Status']])] : [['id', 'Document'], ['location_name', 'Location'], ['reason', 'Reason'], ['status', 'Status'], ['posted_at', 'Posted at']];
  return <main className="content"><p className="eyebrow">STOCK CONTROL</p><h1>Inventory</h1><p className="muted">Available stock is on hand minus active reservations. Batch and serial posting is a later optional module.</p><div className="form-actions">{can('inventory.adjust') && <><button onClick={() => start('adjustments')}><Icon name="add" />New adjustment</button><button className="secondary" onClick={() => start('counts')}><Icon name="inventory" />Record stock count</button></>}{can('inventory.reserve') && <button className="secondary" onClick={() => start('reservations')}><Icon name="reserve" />Reserve stock</button>}{can('inventory.approve') && <button className="secondary" onClick={reconcile}><Icon name="refresh" />Reconcile ledger</button>}</div><ErrorNotice error={error || refs.error} />{report && <p role="status" className="card">{report.reconciled ? 'Ledger and reservations reconcile.' : `Reconciliation needs review: ${JSON.stringify(report.issues)}`} {report.balances} balances, {report.movements} movements.</p>}<div className="tabs">{['balances', 'movements', 'adjustments', 'counts', 'reservations'].map(t => <button className={tab === t ? '' : 'secondary'} key={t} onClick={() => {
        setTab(t);
        setDoc(null);
      }}><Icon name="list" />{t}</button>)}</div><DashboardDrilldown query={filters} clearTo={`/inventory?tab=${tab}`}/><RemoteTable label={`Inventory ${tab}`} key={`${tab}:${filters}`} path={`inventory/${tab}${filters ? `?${filters}` : ''}`} columns={columns} onSelect={['adjustments', 'counts', 'reservations'].includes(tab) ? select : undefined} revision={revision} />{form && <Modal key={form.key} title={<>New {form.kind}</>} onClose={() => setForm(null)}><SaveForm onSave={save} label={form.kind === 'reservations' ? 'Reserve stock' : 'Save for approval'} onCancel={() => setForm(null)} validate={() => requireLines(lines, {
        exactlyOne: form.kind === 'reservations'
      })}><FormSection title="Stock document" description="Choose the stock location and record why this document is needed."><LookupSelect name="location_id" label="Stock location" endpoint="catalog/locations" options={refs.data['catalog/locations']?.data || []} />{form.kind === 'reservations' ? <><Input name="reference" label="Collection or reservation reference" maxLength={120} /><Input name="minutes" label="Expires in minutes" value="15" type="number" min="1" max="1440" /><p>Select exactly one variant for a reservation.</p></> : <Input name="reason" label="Reason" />}</FormSection><FormSection title="Items" description={form.kind === 'counts' ? 'Enter the quantities counted at this location.' : form.kind === 'adjustments' ? 'Use a positive or negative quantity for each stock change.' : 'Choose the variant and quantity to reserve.'}><LineEditor lines={lines} setLines={setLines} count={form.kind === 'counts'} signed={form.kind === 'adjustments'} /></FormSection></SaveForm></Modal>}{doc && <section className="card"><h2>{tab} #{doc.id} · {doc.status}</h2><p>{doc.reason}</p><TableViewport><table><thead><tr><th>Variant</th><th className="numeric">Expected</th><th className="numeric">Counted</th><th className="numeric">Change</th></tr></thead><tbody>{doc.items.map(line => <tr key={line.id}><td>{line.sku || line.variant_id}</td><td className="numeric">{line.expected_qty ?? '—'}</td><td className="numeric">{line.counted_qty ?? '—'}</td><td className="numeric">{line.quantity_delta ?? line.variance_qty}</td></tr>)}</tbody></table></TableViewport>{can('inventory.approve') && doc.status === 'draft' && <button disabled={busy} onClick={post}><Icon name="check" />{busy ? 'Posting…' : 'Approve and post'}</button>}</section>}</main>;
}
