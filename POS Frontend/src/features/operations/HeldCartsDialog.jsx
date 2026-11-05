import TableViewport from '../../components/ui/TableViewport';
import { useEffect, useRef, useState } from 'react';
import Modal from '../../components/Modal';
import Icon from '../../components/Icon';
import LoadingState from '../../components/LoadingState';
import { ErrorNotice } from '../../components/WorkspaceForm';
import { workspaceApi } from '../../api/workspace';
import { requestKey } from './OperationUI';
import { destination } from '../dashboard/dashboardData';
import { formatUpdatedAt } from '../../components/dashboard/format';

export default function HeldCartsDialog({ branchId, timezone, canRecall, onRecall, onClose }) {
  const [page, setPage] = useState(1), [revision, setRevision] = useState(0);
  const [state, setState] = useState({ loading: true }), [busy, setBusy] = useState(false), [error, setError] = useState(null), [confirm, setConfirm] = useState(null);
  const alive = useRef(true), inFlight = useRef(false);
  useEffect(() => { alive.current = true; return () => { alive.current = false; }; }, []);
  useEffect(() => {
    const controller = new AbortController(); setState({ loading: true });
    workspaceApi.get(destination('pos/held-carts', { page, branch_id: branchId }), { signal: controller.signal }).then(result => {
      if (!controller.signal.aborted) { if (page > result.last_page) setPage(Math.max(1, result.last_page)); else setState({ result }); }
    }).catch(failure => { if (!controller.signal.aborted) setState({ error: failure }); });
    return () => controller.abort();
  }, [page, revision, branchId]);
  async function recall(row) {
    if (!canRecall || inFlight.current) return;
    inFlight.current = true; setBusy(true); setError(null);
    try { const result = await workspaceApi.get(`pos/held-carts/${row.id}`); if (alive.current) { onRecall(result.data); onClose(); } }
    catch (failure) { if (alive.current) setError(failure); }
    finally { inFlight.current = false; if (alive.current) setBusy(false); }
  }
  async function discard() {
    if (!confirm || inFlight.current) return;
    inFlight.current = true; setBusy(true); setError(null);
    try { await workspaceApi.save(`pos/held-carts/${confirm.row.id}/discard`, { idempotency_key: confirm.key, version: confirm.row.version }); if (alive.current) { setConfirm(null); setRevision(value => value + 1); } }
    catch (failure) { if (alive.current) setError(failure); }
    finally { inFlight.current = false; if (alive.current) setBusy(false); }
  }
  const result = state.result;
  return <Modal title="Your held carts" size="large" onClose={() => { if (!busy) onClose(); }}><p>Saved carts belong to you. Recalling loads items for a fresh quote; it does not record a sale or reserve stock.</p>
    <ErrorNotice error={error || state.error} onRetry={() => { setError(null); setConfirm(null); setRevision(value => value + 1); }}/><LoadingState busy={Boolean(state.loading)} label="Loading your held carts…" variant="table" columns={4}/>
    {!canRecall && <p className="error">Hold or clear your current cart before recalling another one.</p>}
    {confirm ? <section className="card"><h3>Discard {confirm.row.label || `cart #${confirm.row.id}`}?</h3><p>This removes it from your active held-cart list and retains its history.</p><div className="form-actions"><button disabled={busy} onClick={discard}><Icon name="delete"/>Discard held cart</button><button className="secondary" disabled={busy} onClick={() => setConfirm(null)}>Keep cart</button></div></section> : <>
      {result?.data.length > 0 ? <TableViewport className="table-scroll"><table><caption className="sr-only">Your saved carts</caption><thead><tr><th scope="col">Cart</th><th scope="col">Items</th><th scope="col">Saved</th><th scope="col">Actions</th></tr></thead><tbody>{result.data.map(row => <tr key={row.id}><th scope="row">{row.label || `Cart #${row.id}`}</th><td>{row.item_count}</td><td>{formatUpdatedAt(row.updated_at, timezone)}</td><td><div className="form-actions"><button disabled={busy || !canRecall} onClick={() => recall(row)}><Icon name="play"/>Recall</button><button className="secondary" disabled={busy} onClick={() => setConfirm({ row, key: requestKey() })}><Icon name="delete"/>Discard</button></div></td></tr>)}</tbody></table></TableViewport> : !state.loading && !state.error && <p className="muted">No held carts in this scope.</p>}
      {result && <nav className="dashboard-pagination" aria-label="Held cart results"><button className="secondary" disabled={busy || page <= 1} onClick={() => setPage(value => value - 1)}><Icon name="previous"/>Previous</button><span>Page {page} of {result.last_page} · {result.total} carts</span><button className="secondary" disabled={busy || page >= result.last_page} onClick={() => setPage(value => value + 1)}>Next<Icon name="next"/></button></nav>}
    </>}
  </Modal>;
}
