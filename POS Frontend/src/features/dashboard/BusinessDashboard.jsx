import { PendingApprovals, StockAlerts } from './DashboardWidgets';
import TableViewport from '../../components/ui/TableViewport';
import { Link } from 'react-router-dom';
import Icon from '../../components/Icon';
import PageHeader from '../../components/dashboard/PageHeader';
import DashboardContext from '../../components/dashboard/DashboardContext';
import KpiCard from '../../components/dashboard/KpiCard';
import ChartCard from '../../components/dashboard/ChartCard';
import TrendChart from '../../components/dashboard/TrendChart';
import BranchSwitcher from '../../components/dashboard/BranchSwitcher';
import DateRangeFilter from '../../components/dashboard/DateRangeFilter';
import QuickActionGrid from '../../components/dashboard/QuickActionGrid';
import EmptyState from '../../components/dashboard/EmptyState';
import { formatMoney, formatNumber } from '../../components/dashboard/format';
import { destination } from './dashboardData';

const actions = [
  { label: 'New sale', to: '/pos', permission: 'sales.create', icon: 'pos', description: 'Open the selling desk' },
  { label: 'Add product', to: '/catalog/new', permission: 'products.manage', icon: 'product', description: 'Create a catalog item' },
  { label: 'Purchase orders', to: '/purchasing', permission: 'purchases.view', icon: 'purchasing', description: 'Order and receive goods' },
  { label: 'Stock adjustment', to: '/inventory?tab=adjustments', permissions: ['inventory.adjust', 'inventory.view'], icon: 'inventory', description: 'Review and record adjustments' },
  { label: 'View reports', to: '/reports', permission: 'reports.view', icon: 'reports', description: 'Reconcile posted activity' },
];
const adminActions = [
  { label: 'Manage shifts', to: '/cash', permission: 'cash.open', icon: 'cash', description: 'Review cash sessions' },
  { label: 'User access', to: '/team', permission: 'team.view', icon: 'team', description: 'Review team and roles' },
  ...actions,
];

export default function BusinessDashboard({ state, onFilters, onRefresh, onBranchPage }) {
  const { tenant, filters, responses, errors, updatedAt } = state;
  const summary = responses.summary?.data || {}, meta = responses.summary?.meta || {};
  const owner = tenant.dashboard.profile === 'owner';
  const allowed = widget => state.plan.has(widget);
  const can = permission => tenant.permissions.includes(permission);
  const report = can('reports.view') && allowed('accounting') ? destination('/reports', filters) : undefined;
  const period = { from: filters.from, to: filters.to };
  const scoped = { branch_id: filters.branch_id };
  const scope = filters.branch_id ? tenant.allowed_branches.find(branch => String(branch.id) === String(filters.branch_id))?.name || 'Selected branch' : tenant.branch_scope === 'assigned' && !tenant.allowed_branches.length ? 'No branches assigned' : 'All permitted branches';
  const salesScope = tenant.dashboard.sales_scope === 'own' ? 'Your sales only' : 'Sales in permitted branches';
  const card = { currency: tenant.currency, period, timezone: tenant.timezone, scope };
  const comparison = summary.comparison;
  const change = comparison?.change_percent?.net_sales_excluding_tax;
  const trend = change === null || change === undefined ? undefined : { value: change,
    comparisonLabel: `vs ${comparison.from} – ${comparison.to}`, tone: String(change).startsWith('-') ? 'warning' : 'success' };
  const current = 'Current snapshot · independent of reporting dates.';
  const pending = summary.approvals;
  const branchResult = responses['branch-performance']?.data;
  const products = responses['top-products']?.data;

  return <main className={`content business-dashboard business-dashboard--${owner ? 'owner' : 'admin'}`}>
    <PageHeader eyebrow={tenant.name} title={owner ? 'Business Owner Dashboard' : 'Business Admin Dashboard'}
      description={owner ? 'Business performance across your permitted branches.' : 'Sales, shifts and the operations that need your attention.'}
      updatedAt={updatedAt} timezone={tenant.timezone} context={<DashboardContext scope={scope} salesScope={salesScope} currency={tenant.currency} period={period}/>}
      actions={<><button className="secondary" onClick={onRefresh}><Icon name="refresh"/>Refresh dashboard</button>{can('sales.create') && <Link className="button-link" to="/pos"><Icon name="pos"/>New sale</Link>}</>}/>
    <section className="card dashboard-controls" aria-label="Dashboard filters">
      <BranchSwitcher branches={tenant.allowed_branches} value={filters.branch_id || ''} includeAll onChange={branch_id => onFilters({ ...filters, branch_id })}/>
      <DateRangeFilter value={period} timezone={tenant.timezone} onApply={dates => onFilters({ ...filters, ...dates })}/>
    </section>
    <section className="dashboard-kpi-grid" aria-label="Key performance indicators">
      {allowed('sales') && <KpiCard {...card} title="Net sales excluding tax" value={summary.sales?.totals.net_sales_excluding_tax} format="money" icon="pos" trend={trend}
        definition="Original invoices less posted return/cancellation credits and net tax." to={report}/>}
      {owner && allowed('sales') && <KpiCard {...card} title="Invoices issued" value={summary.sales?.invoice_count} icon="sales" definition="Original invoices in this period, including subsequently voided invoices." to={report}/>}
      {owner && allowed('accounting') && <KpiCard title="Current receivables" value={summary.accounting?.current_balances.receivables} currency={tenant.currency} format="money" icon="billing" scope={`Current snapshot · ${scope}`} definition="Current lifetime balances due in the selected branches." to={report}/>} 
      {!owner && allowed('shift') && <KpiCard title="Open shifts" scope={`Current snapshot · ${scope}`} icon="cash" value={summary.shift?.open_sessions}
        definition={`${current} ${summary.shift?.scope === 'own' ? 'Your sessions only.' : 'Permitted sessions.'}`}/>}
      {!owner && allowed('purchasing') && <KpiCard title="Open purchase orders" scope={`Current snapshot · ${scope}`} value={summary.purchasing?.open_orders} icon="purchasing" definition={`Approved or partially received orders. ${current}`}
        to={can('purchases.view') ? destination('/purchasing', { ...scoped, tab: 'orders', status: 'open' }) : undefined}/>}
      {allowed('inventory') && <KpiCard title="Low stock locations" scope={`Current snapshot · ${scope}`} value={summary.inventory?.low_stock_locations} icon="warning"
        definition={summary.inventory?.low_stock_threshold == null ? 'Configure a low stock threshold in Workspace tools.' : `SKU-location pairs above zero and at or below ${summary.inventory.low_stock_threshold} available units. ${current}`}
        to={summary.inventory?.low_stock_threshold == null ? undefined : destination('/inventory', { ...scoped, tab: 'balances', stock_state: 'low' })}/>}
    </section>
    {(allowed('sales-trend') || allowed('branch-performance')) && <div className="dashboard-chart-grid">
      {allowed('sales-trend') && <ChartCard title="Sales trend" description="Net sales excluding tax, after posted returns." period={period} timezone={tenant.timezone} unit={tenant.currency}
        error={errors['sales-trend']} onRetry={onRefresh} actions={report && <Link to={report}>View report<Icon name="forward" size={16}/></Link>}>
        <TrendChart title="Daily net sales excluding tax" unit={tenant.currency} currency={tenant.currency} data={responses['sales-trend']?.data.series.map(point => ({ label: point.date, value: point.net_sales_excluding_tax }))}/>
        {comparison && <p className="dashboard-metadata">Previous period net sales: {formatMoney(comparison.sales.totals.net_sales_excluding_tax, tenant.currency)} ({comparison.from} – {comparison.to}).{change == null && ' Percentage unavailable because the previous value is zero.'}</p>}
      </ChartCard>}
      {allowed('branch-performance') && <ChartCard title="Sales by branch" description="Net sales excluding tax. Negative values include credits for older invoices." period={period} timezone={tenant.timezone} unit={tenant.currency}
        error={errors['branch-performance']} onRetry={onRefresh}>
        {branchResult?.items.length ? <>
          <TrendChart kind="bar" title="Branch net sales excluding tax" unit={tenant.currency} currency={tenant.currency} data={branchResult.items.map(branch => ({ label: branch.name, value: branch.net_sales_excluding_tax }))}/>
          <ul className="dashboard-branch-list">{branchResult.items.map(branch => <li key={branch.id}><Link to={destination('/reports', { ...period, branch_id: branch.id })}>{branch.name}<Icon name="forward" size={14}/></Link><strong className="money-text">{formatMoney(branch.net_sales_excluding_tax, tenant.currency)}</strong></li>)}</ul>
          <nav className="dashboard-pagination" aria-label="Branch results"><button className="secondary" disabled={branchResult.pagination.current_page <= 1} onClick={() => onBranchPage(branchResult.pagination.current_page - 1)}><Icon name="previous"/>Previous branches</button>
            <span>Page {branchResult.pagination.current_page} of {branchResult.pagination.last_page} · {branchResult.pagination.total} branches</span><button className="secondary" disabled={branchResult.pagination.current_page >= branchResult.pagination.last_page} onClick={() => onBranchPage(branchResult.pagination.current_page + 1)}>Next branches<Icon name="next"/></button></nav>
          <p className="dashboard-metadata">Chart shows this page only; dashboard totals cover the full selected scope.</p>
        </> : <EmptyState title="No permitted branches" description="Choose a business with branch access or ask an administrator to review your assignments."/>}
      </ChartCard>}
    </div>}
    {(allowed('inventory') && allowed('alerts') || allowed('approvals')) && <section className="dashboard-section" aria-label="Action required">
      <div className="dashboard-section-heading"><h2>Action required</h2><p className="muted">Current stock exceptions and documents awaiting review.</p></div>
    <div className="dashboard-detail-grid">
      {allowed('inventory') && allowed('alerts') && <StockAlerts response={responses.alerts} tenant={tenant} filters={filters} error={errors.alerts} onRefresh={onRefresh}/>}
      {allowed('approvals') && <PendingApprovals data={pending} tenant={tenant}/>}
    </div>
    </section>}
    <div className="dashboard-detail-grid">
      {allowed('top-products') && <ChartCard title="Top selling products" description="Top five SKUs by net sales excluding tax. Returns of older invoices can produce negative values." period={period} timezone={tenant.timezone} unit={tenant.currency}
        error={errors['top-products']} onRetry={onRefresh}>
        {products?.items.length ? <TableViewport className="table-scroll"><table><caption className="sr-only">Top selling products for the selected scope</caption><thead><tr><th scope="col">Product / SKU</th><th className="numeric" scope="col">Net quantity</th><th className="numeric" scope="col">Net sales</th></tr></thead><tbody>{products.items.map(item => <tr key={item.variant_id}><th scope="row"><Link to={`/catalog/${item.product_id}`}>{item.name}</Link><small className="dashboard-sku">{item.sku}</small></th><td className="numeric">{formatNumber(item.net_units)} {item.unit}</td><td className="money-text">{formatMoney(item.net_sales_excluding_tax, tenant.currency)}</td></tr>)}</tbody></table></TableViewport> : <EmptyState/>}
      </ChartCard>}
      <section className="card dashboard-actions-card"><h2>Quick actions</h2><p className="muted">Open the tools available to your role.</p><QuickActionGrid title="Business tools" permissions={tenant.permissions}
        actions={(owner ? actions : adminActions).filter(action => tenant.branch_scope !== 'assigned' || !['/team', '/catalog/new'].includes(action.to)).filter(action => tenant.sales_scope !== 'own' || !['/team', '/reports', '/catalog/new'].includes(action.to))}/></section>
    </div>
    <section className="dashboard-kpi-grid dashboard-secondary-metrics" aria-label="Additional business indicators">
      {owner && allowed('sales') && <KpiCard {...card} title="Posted return credits" value={summary.sales?.totals.return_credits} format="money" icon="returns" definition="Posted return and cancellation credits including tax, recorded during this period." to={report}/>}
      {allowed('inventory') && <KpiCard title="Out of stock locations" scope={`Current snapshot · ${scope}`} value={summary.inventory?.out_of_stock_locations} icon="inventory" definition={`Recorded SKU-location pairs with available quantity at or below zero. ${current}`}
        to={destination('/inventory', { ...scoped, tab: 'balances', stock_state: 'out' })}/>}
      {owner && allowed('accounting') && <><KpiCard title="Gross profit" icon="reports" scope="Not available" definition="Unavailable until verified cost of sales is supported."/>
        <KpiCard {...card} title="Net external collections" value={summary.accounting?.net_external_collections} format="money" icon="cash" definition="Recorded external payments less refunds during this period; exchange credits are separate." to={report}/></>}
    </section>
    {!state.plan.widgets.length && <EmptyState title="No dashboard widgets available" description="Your current permissions do not include dashboard data. Review business access with your administrator."/>}
    <details className="dashboard-definitions"><summary>Metric definitions and freshness</summary><p>Period figures use {tenant.timezone}. Invoices use sale time; posted return and cancellation credits use posting time, including credits for older invoices. Net sales exclude net tax. Original voided invoices and cancellation credits are both retained to prevent counting a cancellation twice.</p>
      <p>Stock, purchasing, approvals, open shifts and current receivables are current snapshots. Gross profit requires verified cost of sales. Cash collections are separate from revenue.</p><p>Updated time is the oldest successful widget snapshot. Server cache age is limited to {meta.max_cache_age_seconds ?? 'the configured maximum'} seconds; audited application changes invalidate it. Refresh dashboard reloads your access and all widgets.</p>
      {meta.includes_unassigned_locations && <p>All-branch operational counts also include permitted stock locations without a branch.</p>}</details>
  </main>;
}
