import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { workspaceApi } from '../../api/workspace';
import { ErrorNotice, Input, SaveForm } from '../../components/WorkspaceForm';
import LoadingState from '../../components/LoadingState';
import DashboardDrilldown, { drilldownQuery } from './DashboardDrilldown';

export default function ReportsPage() {
  const [search, setSearch] = useSearchParams();
  const [revision, setRevision] = useState(0);
  const [reportBranch, setReportBranch] = useState(search.get('branch_id') || '');
  const [context, setContext] = useState({});
  const [reportError, setReportError] = useState(null);
  const query = drilldownQuery(search, ['from', 'to', 'branch_id']);
  useEffect(() => { setReportBranch(search.get('branch_id') || ''); }, [query]);
  useEffect(() => {
    const controller = new AbortController();
    workspaceApi.get('me/context', { signal: controller.signal, notifications: false }).then(result => {
      if (!controller.signal.aborted) setContext({ tenant: result.data.active_tenant });
    }).catch(error => { if (!controller.signal.aborted) setContext({ error }); });
    return () => controller.abort();
  }, []);
  function run(form) {
    setReportError(null);
    const values = { from: form.get('from'), to: form.get('to') };
    if (form.get('branch_id')) values.branch_id = form.get('branch_id');
    setSearch(values); setRevision(value => value + 1);
  }
  return <main className="content"><p className="eyebrow">FINANCIAL CONSISTENCY</p><h1>Reports</h1>
    <ErrorNotice error={context.error}/><ErrorNotice error={reportError} onRetry={() => setRevision(value => value + 1)}/><section className="card"><SaveForm key={query} onSave={run} label="Run report">
      <div className="operation-line"><Input name="from" label="From date" type="date" value={search.get('from') || ''}/><Input name="to" label="Through date" type="date" value={search.get('to') || ''}/>
        <label>Branch<select name="branch_id" aria-label="Report branch" value={reportBranch} onChange={event => setReportBranch(event.target.value)}><option value="">All permitted branches</option>
          {reportBranch && !context.tenant?.allowed_branches.some(branch => String(branch.id) === reportBranch) && <option value={reportBranch}>Selected branch {reportBranch}</option>}
          {context.tenant?.allowed_branches.map(branch => <option key={branch.id} value={branch.id}>{branch.name}</option>)}</select></label></div>
    </SaveForm></section><DashboardDrilldown query={search.get('branch_id') ? `branch_id=${encodeURIComponent(search.get('branch_id'))}` : ''} clearTo={`/reports?${new URLSearchParams({ from: search.get('from') || '', to: search.get('to') || '' })}`}/>
    <ReportOutput key={`${query}:${revision}`} query={query} enabled={Boolean(search.get('from') && search.get('to'))} onError={setReportError}/>
  </main>;
}

function ReportOutput({ query, enabled, onError }) {
  const [state, setState] = useState({ loading: enabled });
  useEffect(() => {
    onError(null);
    if (!enabled) return;
    const controller = new AbortController();
    setState({ loading: true });
    workspaceApi.get(`reports/summary?${query}`, { signal: controller.signal }).then(result => { if (!controller.signal.aborted) setState({ report: result.data }); })
      .catch(error => { if (!controller.signal.aborted) { setState({ loading: false }); onError(error); } });
    return () => controller.abort();
  }, [query, enabled, onError]);
  const report = state.report;
  return <><LoadingState busy={Boolean(state.loading)} label="Loading report…" variant="cards" rows={4}/>{report && <>
    <p>{report.period.from} through {report.period.to} · {report.period.timezone}</p><section className="cards">{Object.entries(report.totals).map(([key, value]) => <article className="card" key={key}><h3>{key.replaceAll('_', ' ')}</h3><strong>{value}</strong></article>)}</section>
    <section className="card"><h2>Payment methods</h2>{Object.entries(report.methods).map(([name, values]) => <p key={name}>{name}: collected {values.collections}, refunded/transferred {values.refunds}</p>)}<p>Current receivables: {report.current_balances.due}; refunds due: {report.current_balances.refund_due}</p>
      <p role="status">{report.reconciled ? 'Posted sale, return and payment totals reconcile.' : `Review discrepancies: ${JSON.stringify(report.issues)}`}</p><p className="muted">{report.basis}</p></section>
  </>}</>;
}
