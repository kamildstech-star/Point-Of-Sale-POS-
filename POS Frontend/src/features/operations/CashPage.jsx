import TableViewport from '../../components/ui/TableViewport';
import { FormDialog } from "../../components/Modal";
import { useEffect, useState } from 'react';
import DashboardDrilldown, { drilldownQuery } from './DashboardDrilldown';
import { Link, useSearchParams } from 'react-router-dom';
import { useAuth } from '../../auth/AuthProvider';
import { workspaceApi } from '../../api/workspace';
import { ErrorNotice, Input, SaveForm, Select } from '../../components/WorkspaceForm';
import LookupSelect from '../../components/LookupSelect';
import { RemoteTable, requestKey, useResources } from './OperationUI';
export default function CashPage() {
  const {
    user
  } = useAuth();
  const can = p => user.permissions.includes(p);
  const [search] = useSearchParams();
  const filters = drilldownQuery(search, ['branch_id', 'own', 'document_id']);
  const [revision, setRevision] = useState(0);
  const [key, setKey] = useState(requestKey);
  const [report, setReport] = useState(null);
  const [error, setError] = useState(null);
  const registerPath = `operations/registers${search.get('branch_id') ? `?branch_id=${encodeURIComponent(search.get('branch_id'))}` : ''}`;
  const refs = useResources([registerPath]);
  const [history, setHistory] = useState(Boolean(search.get('document_id')));
  useEffect(() => {
    const controller = new AbortController(); const id = search.get('document_id');
    setReport(null); setError(null);
    if (id && /^\d+$/.test(id)) workspaceApi.get(`cash/sessions/${id}/reconciliation${search.get('own') === '1' ? '?own=1' : ''}`, { signal: controller.signal })
      .then(result => { if (!controller.signal.aborted) setReport(result.data); }).catch(failure => { if (!controller.signal.aborted) setError(failure); });
    return () => controller.abort();
  }, [filters]);
  async function reconcile(row) {
    setError(null);
    try {
      setReport((await workspaceApi.get(`cash/sessions/${row.id}/reconciliation${search.get('own') === '1' ? '?own=1' : ''}`)).data);
      setKey(requestKey());
    } catch (failure) {
      setError(failure);
    }
  }
  async function open(form) {
    await workspaceApi.save('cash/sessions', {
      idempotency_key: key,
      register_id: Number(form.get('register_id')),
      opening_cash: form.get('opening_cash')
    });
    setRevision(r => r + 1);
    setKey(requestKey());
  }
  async function move(form) {
    await workspaceApi.save(`cash/sessions/${report.session.id}/movements`, {
      idempotency_key: key,
      movement_type: form.get('movement_type'),
      amount: form.get('amount'),
      reason: form.get('reason')
    });
    await reconcile(report.session);
  }
  async function close(form) {
    await workspaceApi.save(`cash/sessions/${report.session.id}/close`, {
      idempotency_key: key,
      counted_cash: form.get('counted_cash')
    });
    await reconcile(report.session);
    setRevision(r => r + 1);
  }
  return <main className="content"><p className="eyebrow">REGISTER CONTROL</p><h1>Register shifts</h1><p>Reconcile collections, refunds, cash movements and posted expenses before closing. Closing stores expected cash, counted cash and variance.</p><Link to="/pos">Open POS</Link><ErrorNotice error={error || refs.error} /><label className="checkbox"><input type="checkbox" checked={history} onChange={e => setHistory(e.target.checked)} aria-label={"Include closed shifts"} />Include closed shifts</label><DashboardDrilldown query={filters} clearTo="/cash"/><RemoteTable key={`${history}:${filters}`} path={`cash/sessions${history || filters ? '?' : ''}${history ? 'history=1' : ''}${history && filters ? '&' : ''}${filters}`} columns={[["id", "Shift"], ["register_id", "Register"], ["status", "Status"], ["opened_at", "Opened"], ["opening_cash", "Opening cash"], ["variance", "Closing variance"]]} onSelect={reconcile} revision={revision} /><FormDialog title={<>Open shift</>} trigger="Open register shift" defaultOpen={search.get('open') === '1'}><SaveForm onSave={open} label="Open register shift"><LookupSelect name="register_id" label="Register" endpoint={registerPath} options={refs.data[registerPath]?.data || []} /><Input name="opening_cash" label="Counted opening cash" value="0.00" /></SaveForm></FormDialog>{report && <section className="card" key={key}><h2>Shift #{report.session.id} · {report.session.status}</h2><TableViewport><table><thead><tr><th>Method</th><th>Collected</th><th>Refunded / transferred</th><th>Net</th></tr></thead><tbody>{report.methods.map(m => <tr key={m.method_id}><td>{m.name}</td><td className="numeric">{m.collections ?? m.amount}</td><td>{m.refunds ?? '0'}</td><td className="numeric">{m.amount}</td></tr>)}</tbody></table></TableViewport><p>Expected cash: <strong>{report.expected_cash}</strong></p><p>Posted cash expenses: {report.cash_expenses}; cash movements: {report.cash_movements}; cash refunds: {report.cash_refunds}</p><p className="muted">{report.basis}</p>{report.session.status === 'open' && <>{can('cash.move') && <><h3>Record cash in / out</h3><FormDialog title={"Record cash in / out"} trigger="Record cash in / out"><SaveForm onSave={move} label="Post cash movement"><Select name="movement_type" label="Direction" options={[{
                id: 'in',
                name: 'Cash in'
              }, {
                id: 'out',
                name: 'Cash out'
              }]} /><Input name="amount" label="Cash movement amount" /><Input name="reason" label="Cash movement reason" /></SaveForm></FormDialog></>}{can('cash.close') && <><h3>Close shift</h3><FormDialog title={"Close shift"} trigger="Close shift"><SaveForm onSave={close} label="Close shift and store variance"><Input name="counted_cash" label="Actual counted cash" /></SaveForm></FormDialog></>}</>}{report.session.status === 'closed' && <p>Counted {report.session.counted_cash}; closing expected {report.session.expected_cash}; variance {report.session.variance}</p>}</section>}</main>;
}
