import { useEffect, useState } from 'react';
import { Link, Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../auth/AuthProvider';
import { workspaceApi } from '../../api/workspace';
import ThemeToggle from '../../theme/ThemeToggle';
import Icon from '../../components/Icon';
import LoadingState from '../../components/LoadingState';
import { ErrorNotice } from '../../components/WorkspaceForm';
import PageHeader from '../../components/dashboard/PageHeader';
import KpiCard from '../../components/dashboard/KpiCard';
import ChartCard from '../../components/dashboard/ChartCard';
import TrendChart from '../../components/dashboard/TrendChart';
import DateRangeFilter from '../../components/dashboard/DateRangeFilter';
import EmptyState from '../../components/dashboard/EmptyState';
import RouteAccessibility from '../../components/layout/RouteAccessibility';
import { dashboardError, defaultPeriod, destination, sameDashboardFilters } from './dashboardData';

export default function PlatformWorkspace() {
  const { user, logout } = useAuth(), location = useLocation();
  const [filters, setFilters] = useState(() => defaultPeriod('UTC')), [revision, setRevision] = useState(0), [error, setError] = useState(null), [leaving, setLeaving] = useState(false);
  async function signOut() { if (leaving) return; setLeaving(true); setError(null); try { await logout(); } catch (failure) { setError(failure); } finally { setLeaving(false); } }
  if (location.pathname === '/platform') return <Navigate to="/platform/dashboard" replace/>;
  return <div className="workspace app-shell platform-shell" data-density="compact">
    <a className="skip-link" href="#workspace-content">Skip to content</a>
    <RouteAccessibility businessId="platform" pageLabel="Platform dashboard"/>
    <aside className="sidebar app-sidebar" aria-label="Platform navigation"><Link className="wordmark sidebar-brand" to="/platform/dashboard"><Icon name="store"/><span>Universal POS</span></Link><p className="sidebar-label">Platform workspace</p><nav aria-label="Platform navigation"><Link className="sidebar-link active" to="/platform/dashboard" aria-current="page"><Icon name="overview"/>Dashboard</Link>{user.active_tenant && <Link className="sidebar-link" to="/"><Icon name="businesses"/>Business workspace</Link>}</nav><p className="sidebar-label">Aggregate platform access</p></aside>
    <div className="workspace-main"><header className="topbar app-topbar"><div className="topbar-context"><nav aria-label="Platform breadcrumb"><Link to="/platform/dashboard">Platform dashboard</Link></nav>{user.active_tenant && <Link className="platform-business-return" to="/">Business workspace</Link>}</div><div className="topbar-actions"><ThemeToggle/><span className="staff-name">{user.name}</span><button className="secondary" onClick={signOut} disabled={leaving}><Icon name="logout"/>{leaving ? 'Signing out…' : 'Sign out'}</button></div></header>
      <div id="workspace-content" tabIndex={-1}><ErrorNotice error={error}/>{location.pathname !== '/platform/dashboard' ? <main className="content"><h1>Platform page not found</h1><Link to="/platform/dashboard">Back to platform dashboard</Link></main> : <PlatformSnapshot key={`${user.id}:${JSON.stringify(filters)}:${revision}`} userId={user.id} filters={filters} onFilters={next => { if (!sameDashboardFilters(filters, next)) setFilters(next); }} onRefresh={() => setRevision(value => value + 1)}/>}</div>
    </div>
  </div>;
}

function PlatformSnapshot({ userId, filters, onFilters, onRefresh }) {
  const [state, setState] = useState({ loading: true });
  useEffect(() => {
    const controller = new AbortController(), config = { signal: controller.signal, notifications: false };
    async function load() {
      const context = (await workspaceApi.get('platform/context', config)).data;
      if (controller.signal.aborted) return;
      if (String(context.user_id) !== String(userId) || context.scope !== 'platform' || !context.permissions?.includes('dashboard.platform.view')) throw dashboardError('Platform access changed. Refresh your session.');
      if (!context.dashboard_enabled) return { disabled: true };
      const response = await workspaceApi.get(destination('platform/dashboard', filters), config), meta = response.meta;
      if (String(meta?.user_id) !== String(userId) || meta?.scope !== 'platform' || meta?.timezone !== 'UTC' || meta?.from !== filters.from || meta?.to !== filters.to) throw dashboardError('Platform response scope changed. Refresh the dashboard.');
      return response;
    }
    load().then(response => { if (!controller.signal.aborted && response) setState(response); }).catch(error => { if (!controller.signal.aborted) setState({ error }); });
    return () => controller.abort();
  }, [userId, filters.from, filters.to]);
  if (state.loading) return <main className="content" aria-busy="true"><LoadingState label="Loading platform overview…" variant="cards"/></main>;
  if (state.error) return <main className="content"><h1>Platform dashboard</h1><ErrorNotice error={state.error} onRetry={onRefresh}/></main>;
  if (state.disabled) return <main className="content"><h1>Platform dashboard</h1><EmptyState title="Platform pilot is not enabled" description="An authorized platform operator can enable this dashboard during the controlled rollout."/><button className="secondary" onClick={onRefresh}><Icon name="refresh"/>Refresh access</button></main>;
  const { data, meta } = state, subs = data.subscriptions;
  const count = status => subs ? subs[status] ?? 0 : undefined;
  return <main className="content business-dashboard platform-dashboard">
    <PageHeader eyebrow="PLATFORM" title="Platform dashboard" description="Platform totals and processing exceptions. Business records require separate membership." updatedAt={meta.generated_at} timezone="UTC" actions={<button className="secondary" onClick={onRefresh}><Icon name="refresh"/>Refresh dashboard</button>}/>
    <section className="card dashboard-controls platform-period" aria-label="Platform reporting dates"><DateRangeFilter value={filters} timezone="UTC" onApply={onFilters}/><p className="dashboard-metadata">Creation trend: selected UTC dates. Status and incident counts: current snapshots.</p></section>
    <section className="dashboard-kpi-grid" aria-label="Platform indicators"><KpiCard title="Non-archived tenants" value={data.tenants.non_archived} icon="businesses" definition="Current tenant records excluding archived businesses; not a count of paid accounts."/>
      <KpiCard title="Recorded active subscriptions" value={count('active')} icon="billing" definition="Current active subscription records, including legacy/grandfathered policies. Not a claim of paid access."/>
      <KpiCard title="Trialing subscriptions" value={count('trialing')} icon="billing" definition="Current subscription records in trialing status."/>
      <KpiCard title="Monthly recurring revenue" icon="cash" definition={data.mrr_unavailable_reason}/></section>
    <div className="dashboard-detail-grid"><ChartCard title="Tenant creation trend" description="All tenant records created in this period, including those later archived." period={filters} timezone="UTC" unit="Tenants"><TrendChart title="Tenants created per UTC day" unit="Tenants" data={data.tenant_growth.map(point => ({ label: point.date, value: point.created }))}/></ChartCard>
      <ChartCard title="Subscription status" description="Current recorded status, including legacy subscriptions." timezone="UTC" unit="Subscriptions">{subs ? <TrendChart kind="bar" title="Recorded subscription statuses" unit="Subscriptions" data={Object.entries(subs).map(([label, value]) => ({ label: label.replaceAll('_', ' '), value }))}/> : <EmptyState title="Subscription counts unavailable"/>}</ChartCard></div>
    <section aria-label="Platform processing exceptions"><h2>Processing exceptions</h2><div className="dashboard-kpi-grid">{[
      ['Failed billing events', 'failed_billing_events', 'Verified billing event records whose processing failed. Raw payloads and provider IDs are not shown.'],
      ['Pending billing events', 'pending_billing_events', 'Verified billing event records pending or processing. This does not estimate a provider outage.'],
      ['Unpaid provider invoices', 'unpaid_provider_invoices', 'Recorded open or uncollectible SaaS invoices; not a count of confirmed failed payment attempts.'],
      ['Failed queue jobs', 'failed_queue_jobs', 'Failed queue record count, unavailable if the queue failure store is not configured.']
    ].map(([title, field, definition]) => <KpiCard key={field} title={title} value={data.incidents[field]} icon="warning" definition={definition}/>)}</div></section>
    <details className="dashboard-definitions"><summary>Data definitions and access</summary><p>{data.basis}</p><p>Monthly recurring revenue is unavailable until authoritative recurring billing amounts are materialized. Tenant support details and billing documents are not exposed by this aggregate view.</p></details>
  </main>;
}
