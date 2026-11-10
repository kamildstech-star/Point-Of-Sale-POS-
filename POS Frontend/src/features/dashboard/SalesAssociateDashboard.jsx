import { Link } from 'react-router-dom';
import PageHeader from '../../components/dashboard/PageHeader';
import DashboardContext from '../../components/dashboard/DashboardContext';
import BranchSwitcher from '../../components/dashboard/BranchSwitcher';
import DateRangeFilter from '../../components/dashboard/DateRangeFilter';
import KpiCard from '../../components/dashboard/KpiCard';
import ChartCard from '../../components/dashboard/ChartCard';
import TrendChart from '../../components/dashboard/TrendChart';
import QuickActionGrid from '../../components/dashboard/QuickActionGrid';
import TableViewport from '../../components/ui/TableViewport';
import EmptyState from '../../components/dashboard/EmptyState';
import Icon from '../../components/Icon';
import { formatMoney, formatUpdatedAt } from '../../components/dashboard/format';
import { DashboardPagination } from './SpecialistWidgets';
import { destination } from './dashboardData';

export default function SalesAssociateDashboard({ state, onFilters, onRefresh, onPage }) {
  const { tenant, filters, responses, updatedAt } = state, data = responses['personal-sales']?.data;
  const scope = tenant.allowed_branches.find(branch => String(branch.id) === String(filters.branch_id))?.name || 'Permitted branches';
  const period = { period: filters, timezone: tenant.timezone, scope: 'Your invoices only' };
  const actions = [
    { label: 'Find product', to: '/catalog', permission: 'products.view', icon: 'product' },
    { label: 'Find customer', to: '/operations/customers', permission: 'customers.view', icon: 'customer' },
    { label: 'New sale', to: destination('/pos', { own: 1, branch_id: filters.branch_id }), permission: 'sales.create', icon: 'pos' },
    { label: 'Your receipts', to: destination('/sales', { own: 1, branch_id: filters.branch_id, from: filters.from, to: filters.to }), permission: 'sales.view', icon: 'sales' },
  ];
  return <main className="content business-dashboard staff-dashboard staff-dashboard--associate">
    <PageHeader eyebrow={tenant.name} title="Sales Associate Dashboard" description="Your sales and the tools for helping the next customer." timezone={tenant.timezone} updatedAt={updatedAt} context={<DashboardContext scope={scope} salesScope="Your sales only" currency={tenant.currency} period={filters}/>}
      actions={<button className="secondary" onClick={onRefresh}><Icon name="refresh"/>Refresh dashboard</button>}/>
    <section className="card dashboard-controls" aria-label="Dashboard filters"><BranchSwitcher includeAll branches={tenant.allowed_branches} value={filters.branch_id || ''} onChange={branch_id => onFilters({ ...filters, branch_id })}/><DateRangeFilter value={filters} timezone={tenant.timezone} onApply={dates => onFilters({ ...filters, ...dates })}/></section>
    <QuickActionGrid title="Selling tools" actions={actions} permissions={tenant.permissions}/>
    <section className="dashboard-kpi-grid" aria-label="Your sales indicators">
      <KpiCard {...period} title="Your net sales excluding tax" format="money" currency={tenant.currency} value={data?.sales.totals.net_sales_excluding_tax} icon="pos" definition="Your original invoices less posted credits against them and net tax."/>
      <KpiCard {...period} title="Your invoices issued" value={data?.sales.invoice_count} icon="sales" definition="Your original invoices in the period, including subsequently voided invoices."/>
      {tenant.permissions.includes('customers.view') && <KpiCard {...period} title="Customers on your invoices" value={data?.customers_on_invoices} icon="customer" definition="Distinct named customers on your invoices in this period. This is not an assigned-customer count."/>}
      <KpiCard title="Assigned customers" icon="customer" definition="Customer assignments and follow-ups are not stored in this system."/>
    </section>
    <ChartCard title="Your sales trend" description="Your recognized net sales excluding tax, including posted credits for older invoices." period={filters} timezone={tenant.timezone} unit={tenant.currency}><TrendChart title="Your daily net sales excluding tax" currency={tenant.currency} unit={tenant.currency} data={data?.series.map(point => ({ label: point.date, value: point.net_sales_excluding_tax }))}/></ChartCard>
    <ChartCard title="Your recent receipts" description="Only your invoices in these dates and permitted branches." period={filters} timezone={tenant.timezone} unit={tenant.currency}>
      {data?.receipts.items.length ? <TableViewport label="Personal receipts"><table><caption className="sr-only">Your recent receipts</caption><thead><tr><th scope="col">Receipt</th><th scope="col">Sold at</th><th scope="col" className="numeric">Invoice total</th><th scope="col">Status</th></tr></thead><tbody>{data.receipts.items.map(sale => <tr key={sale.id}><th scope="row"><Link to={destination(`/sales/${sale.id}`, { own: 1 })}>{sale.invoice_no}</Link></th><td>{formatUpdatedAt(sale.sold_at, tenant.timezone)}</td><td className="money-text">{formatMoney(sale.grand_total, sale.currency_code)}</td><td>{sale.payment_status}</td></tr>)}</tbody></table></TableViewport> : <EmptyState title="No personal receipts for this view"/>}
      <DashboardPagination data={data?.receipts.pagination} label="Personal receipt results" onPage={page => onPage('receipts', page)}/>
    </ChartCard>
    <details className="dashboard-definitions"><summary>Scope and definitions</summary><p>{data?.basis}</p></details>
  </main>;
}
