import { Link } from 'react-router-dom';
import Icon from '../../components/Icon';
import PageHeader from '../../components/dashboard/PageHeader';
import DashboardContext from '../../components/dashboard/DashboardContext';
import BranchSwitcher from '../../components/dashboard/BranchSwitcher';
import DateRangeFilter from '../../components/dashboard/DateRangeFilter';
import QuickActionGrid from '../../components/dashboard/QuickActionGrid';
import ChartCard from '../../components/dashboard/ChartCard';
import TrendChart from '../../components/dashboard/TrendChart';
import EmptyState from '../../components/dashboard/EmptyState';
import { PendingApprovals, StockAlerts, SummaryIndicators, StockQuantities, AccountingBasis } from './DashboardWidgets';
import { destination } from './dashboardData';
import SpecialistWidgets from './SpecialistWidgets';

export default function CapabilityDashboard({ state, onFilters, onRefresh, onPage }) {
  const { tenant, filters, responses, errors, plan, updatedAt } = state;
  const data = responses.summary?.data, scope = tenant.allowed_branches.find(branch => String(branch.id) === String(filters.branch_id))?.name || (tenant.branch_scope === 'assigned' && !tenant.allowed_branches.length ? 'No branches assigned' : 'All permitted branches');
  const hasPeriod = plan.has('sales') || plan.has('accounting') || plan.has('inventory-trend');
  const tools = [
    { label: 'New sale', to: destination('/pos', { branch_id: filters.branch_id }), permission: 'sales.create', icon: 'pos' },
    { label: 'Stock balances', to: destination('/inventory', { branch_id: filters.branch_id, tab: 'balances' }), permission: 'inventory.view', icon: 'inventory' },
    { label: 'Purchase orders', to: destination('/purchasing', { branch_id: filters.branch_id, tab: 'orders' }), permission: 'purchases.view', icon: 'purchasing' },
    { label: 'Find product', to: '/catalog', permission: 'products.view', icon: 'product' },
    { label: 'Find customer', to: '/operations/customers', permission: 'customers.view', icon: 'customer' },
    ...(plan.has('accounting') ? [{ label: 'View reports', to: destination('/reports', filters), permission: 'reports.view', icon: 'reports' }] : []),
    { label: 'Expenses', to: destination('/expenses', { branch_id: filters.branch_id }), permission: 'expenses.view', icon: 'expense' },
  ];
  return <main className={`content business-dashboard capability-dashboard capability-dashboard--${plan.kind}`}>
    <PageHeader eyebrow={tenant.name} title={plan.title} description="Your permitted work, with current exceptions first." timezone={tenant.timezone} updatedAt={updatedAt}
      context={<DashboardContext scope={scope} salesScope={plan.has('sales') ? tenant.dashboard.sales_scope === 'own' ? 'Your sales only' : 'Sales in permitted branches' : 'Current operational snapshot'} currency={tenant.currency} period={hasPeriod ? filters : undefined}/>}
      actions={<><button className="secondary" onClick={onRefresh}><Icon name="refresh"/>Refresh dashboard</button>
        {plan.kind === 'inventory' && plan.has('inventory-trend') && tenant.permissions.includes('inventory.adjust') && <Link className="button-link" to={destination('/inventory', { tab: 'adjustments', branch_id: filters.branch_id, create: 1 })}><Icon name="inventory"/>Stock adjustment</Link>}
        {plan.kind === 'purchasing' && plan.has('purchase-orders') && tenant.permissions.includes('purchases.manage') && <Link className="button-link" to={destination('/purchasing', { tab: 'orders', branch_id: filters.branch_id, create: 1 })}><Icon name="purchasing"/>Create purchase order</Link>}
      </>}/>
    {plan.widgets.length > 0 && <section className={`card dashboard-controls${hasPeriod ? '' : ' dashboard-controls--snapshot'}`} aria-label="Dashboard filters">
      <BranchSwitcher branches={tenant.allowed_branches} value={filters.branch_id || ''} includeAll onChange={branch_id => onFilters({ ...filters, branch_id })}/>
      {hasPeriod ? <DateRangeFilter value={filters} timezone={tenant.timezone} onApply={dates => onFilters({ ...filters, ...dates })}/> : <p className="dashboard-metadata">Stock, purchasing, shifts and approvals are current snapshots, independent of reporting dates.</p>}
    </section>}
    <SummaryIndicators state={state}/>
    {(plan.has('alerts') || plan.has('approvals')) && <section className="dashboard-section" aria-label="Action required"><div className="dashboard-section-heading"><h2>Action required</h2><p className="muted">Current exceptions and documents awaiting review.</p></div><div className="dashboard-detail-grid">
      {plan.has('alerts') && <StockAlerts response={responses.alerts} error={errors.alerts} tenant={tenant} filters={filters} onRefresh={onRefresh}/>}
      {plan.has('approvals') && <PendingApprovals data={data?.approvals} tenant={tenant}/>}
    </div></section>}
    <div className="dashboard-detail-grid">
      {plan.has('inventory') && <StockQuantities data={data?.inventory}/>}
      {plan.has('accounting') && <AccountingBasis data={data?.accounting} tenant={tenant} period={filters}/>}
    </div>
    {plan.has('sales-trend') && <ChartCard title={tenant.dashboard.sales_scope === 'own' ? 'Your sales trend' : 'Sales trend'} description="Net sales excluding tax, after posted credits." period={filters} timezone={tenant.timezone} unit={tenant.currency} error={errors['sales-trend']} onRetry={onRefresh}>
      <TrendChart title="Daily net sales excluding tax" unit={tenant.currency} currency={tenant.currency} data={responses['sales-trend']?.data.series.map(point => ({ label: point.date, value: point.net_sales_excluding_tax }))}/>
      {plan.has('accounting') && <Link to={destination('/reports', filters)}>View report<Icon name="forward" size={16}/></Link>}
    </ChartCard>}
    <SummaryIndicators state={state} secondary/>
    {['inventory-trend', 'purchase-orders', 'accounting-trend'].some(plan.has) && <SpecialistWidgets state={state} onRefresh={onRefresh} onPage={onPage}/>}
    {!plan.widgets.length && <EmptyState title="No dashboard metrics available" description="Your current permissions provide workspace tools without access to dashboard figures. Ask an administrator if you need additional access."/>}
    <section className="card dashboard-actions-card"><h2>Quick actions</h2><p className="muted">Tools available through your effective permissions.</p><QuickActionGrid title="Workspace tools" actions={tools} permissions={tenant.permissions}/></section>
    <details className="dashboard-definitions"><summary>Metric definitions and freshness</summary>{['inventory', 'purchasing', 'accounting'].filter(widget => plan.has(widget)).map(widget => data?.[widget]?.basis && <p key={widget}>{data[widget].basis}</p>)}<p>Stock and document counts are current snapshots. Reporting dates use {tenant.timezone}. Refresh reloads current permissions and all widgets together. Each value covers only your permitted branches and sales scope. Quantities remain separated by unit.</p></details>
  </main>;
}
