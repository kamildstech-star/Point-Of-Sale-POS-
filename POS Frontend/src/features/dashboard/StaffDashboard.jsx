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
import EmptyState from '../../components/dashboard/EmptyState';
import QuickActionGrid from '../../components/dashboard/QuickActionGrid';
import { ErrorNotice } from '../../components/WorkspaceForm';
import { formatMoney, formatNumber as displayNumber, formatUpdatedAt } from '../../components/dashboard/format';
import { defaultPeriod, destination } from './dashboardData';

const formatNumber = value => displayNumber(value) ?? 'Unavailable';

function Pagination({ value, label, onChange }) {
  if (!value || value.last_page <= 1) return null;
  return <nav className="dashboard-pagination" aria-label={label}><button className="secondary" disabled={value.current_page <= 1} onClick={() => onChange(value.current_page - 1)}><Icon name="previous"/>Previous</button>
    <span>Page {value.current_page} of {value.last_page} · {value.total} records</span><button className="secondary" disabled={value.current_page >= value.last_page} onClick={() => onChange(value.current_page + 1)}>Next<Icon name="next"/></button></nav>;
}

function Receipts({ data, tenant, error, onRefresh, onPage, own = false, period }) {
  return <ChartCard title={own ? 'Your recent receipts' : 'Recent branch receipts'} description={own ? 'Your invoices in the selected shift and reporting dates. No other staff receipts.' : 'Original invoices in the selected branch and period.'} period={period} timezone={tenant.timezone} unit={tenant.currency} error={error} onRetry={onRefresh}>
    {data?.items?.length ? <><TableViewport className="table-scroll"><table><caption className="sr-only">Recent receipts</caption><thead><tr><th scope="col">Receipt</th><th scope="col">Sold at</th><th className="numeric" scope="col">Invoice total</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead><tbody>{data.items.map(sale => <tr key={sale.id}>
      <th scope="row"><Link to={destination(`/sales/${sale.id}`, own ? { own: 1 } : {})}>{sale.invoice_no}</Link></th><td>{formatUpdatedAt(sale.sold_at, tenant.timezone)}</td><td className="money-text">{formatMoney(sale.grand_total, sale.currency_code)}</td><td>{sale.sale_status} · {sale.payment_status}</td>
      <td>{tenant.permissions.includes('sales.return') && <Link to={`/returns/new/${sale.id}`}>Return<Icon name="returns" size={16}/></Link>}</td>
    </tr>)}</tbody></table></TableViewport><Pagination value={data.pagination} label="Receipt results" onChange={onPage}/></> : <EmptyState title="No receipts for this view" description="Receipts appear after sales are posted in your permitted scope."/>}
  </ChartCard>;
}

export default function StaffDashboard(props) {
  return props.state.tenant.dashboard.profile === 'cashier' ? <CashierDashboard {...props}/> : <ManagerDashboard {...props}/>;
}

function CashierDashboard({ state, onFilters, onRefresh, onPage }) {
  const { tenant, filters, responses, updatedAt } = state;
  const data = responses['cashier-summary']?.data;
  const can = permission => tenant.permissions.includes(permission);
  const selected = data?.selected_session;
  const pos = destination('/pos', { own: 1, branch_id: filters.branch_id, cash_session_id: selected?.id });
  const openShift = destination('/cash', { own: 1, branch_id: filters.branch_id, open: 1 });
  return <main className="content business-dashboard staff-dashboard staff-dashboard--cashier">
    <PageHeader eyebrow={tenant.name} title="Cashier Dashboard" context={<DashboardContext scope={selected?.branch_name || 'Permitted branches'} salesScope="Your register activity" currency={tenant.currency} period={filters}/>} description="Your register, your receipts and a quick start to the next sale." timezone={tenant.timezone} updatedAt={updatedAt}
      actions={<button className="secondary" onClick={onRefresh}><Icon name="refresh"/>Refresh dashboard</button>}/>
    <section className="card cashier-start" aria-label="Selling actions"><div><span className="pill">{selected ? 'SHIFT OPEN' : 'READY TO START'}</span><h2>{selected ? `${selected.register_name} · ${selected.branch_name}` : 'Start with a register shift'}</h2>
      <p className="muted">{selected ? `Opened ${formatUpdatedAt(selected.opened_at, tenant.timezone)} · ${tenant.timezone}` : 'Open an available register before taking payment.'}</p></div>
      <div className="cashier-start-actions">{can('sales.create') && selected && <Link className="button-link cashier-new-sale" to={pos}><Icon name="pos" size={24}/>New Sale</Link>}
        {can('cash.open') && !selected && <Link className="button-link cashier-new-sale" to={openShift}><Icon name="cash" size={24}/>Open register shift</Link>}
        {can('sales.create') && tenant.dashboard.held_sales && <Link className="button-link secondary" to={destination('/pos', { held: 1, own: 1, branch_id: filters.branch_id, cash_session_id: selected?.id })}><Icon name="pause"/>Hold / Recall{data?.held_carts && ` (${data.held_carts.count})`}</Link>}
      </div></section>
    <section className="card staff-dashboard-controls" aria-label="Cashier filters"><BranchSwitcher branches={tenant.allowed_branches} value={filters.branch_id || ''} includeAll onChange={branch_id => onFilters({ branch_id })}/>
      {can('cash.open') && data?.sessions.length > 0 && <label>Your open shift<select aria-label="Your open shift" value={selected?.id || ''} onChange={event => onFilters({ ...filters, cash_session_id: event.target.value })}>
        {data.sessions.map(session => <option key={session.id} value={session.id}>{session.register_name} · {session.branch_name}</option>)}</select></label>}
      <p className="dashboard-metadata">Your activity only · {tenant.currency} · {tenant.timezone}. Up to 20 recent open shifts are listed; all are available in Register shifts.</p>
    </section>
    <section className="dashboard-kpi-grid" aria-label="Your shift indicators">
      {can('cash.open') && <KpiCard title="Your open shifts" value={data?.open_sessions} icon="cash" definition="Current open sessions started by you in the selected branches." to={destination('/cash', { own: 1, branch_id: filters.branch_id })}/>}
      {can('sales.view') && can('cash.open') && <><KpiCard title="Your shift sales" value={data?.shift_totals?.net_invoiced_including_tax} currency={tenant.currency} format="money" icon="pos" definition="Your invoices in this shift, including tax, less posted credits against them. Lifetime shift figure, separate from cash collected."/>
        <KpiCard title="Your shift invoices" value={data?.shift_totals?.invoice_count} icon="sales" definition="Original invoices issued by you in this open shift, including subsequently voided invoices." to={selected ? destination('/sales', { own: 1, cash_session_id: selected.id }) : undefined}/></>}
      {can('sales.create') && <KpiCard title="Your held carts" value={data?.held_carts?.count} icon="pause" definition={tenant.dashboard.held_sales ? 'Your saved carts in the selected branches. No stock is reserved and no payment is recorded.' : 'Saved carts become available after the held-cart migration is applied.'} to={tenant.dashboard.held_sales ? destination('/pos', { held: 1, own: 1, branch_id: filters.branch_id }) : undefined}/>}
    </section>
    {!data && <EmptyState title="No cashier widgets available" description="Your current permissions do not include shift or sales information."/>}
    {can('sales.view') && data?.receipts && <Receipts data={data.receipts} tenant={tenant} own period={filters} onRefresh={onRefresh} onPage={page => onPage('receipts', page)}/>}
    {selected && can('cash.open') && <p className="staff-dashboard-shift-link"><Link to={destination('/cash', { own: 1, document_id: selected.id, branch_id: selected.branch_id })}>Review or close this shift<Icon name="forward" size={16}/></Link></p>}
    <QuickActionGrid actions={[{ label: 'Find product', to: '/catalog', permission: 'products.view', icon: 'product' }, { label: 'Find customer', to: '/operations/customers', permission: 'customers.view', icon: 'customer' },
      { label: 'Your receipts', to: '/sales?own=1', permission: 'sales.view', icon: 'sales' }]} permissions={tenant.permissions}/>
    <details className="dashboard-definitions"><summary>Scope and freshness</summary><p>{data?.basis}</p><p>Receipt dates use {tenant.timezone}. Refresh reloads your access and widgets; the server cache is bounded by its configured TTL. Company profit and other staff sales are not requested by this view.</p></details>
  </main>;
}

function ManagerDashboard({ state, onFilters, onRefresh, onPage }) {
  const { tenant, filters, responses = {}, errors = {}, updatedAt, noBranch } = state;
  const can = permission => tenant.permissions.includes(permission);
  const allowed = widget => state.plan.has(widget);
  const summary = responses.summary?.data;
  const branch = tenant.allowed_branches.find(item => String(item.id) === String(filters.branch_id));
  const report = can('reports.view') && tenant.dashboard.sales_scope !== 'own' ? destination('/reports', filters) : undefined;
  const salesLink = can('sales.view') ? destination('/sales', { from: filters.from, to: filters.to, branch_id: filters.branch_id, ...(tenant.dashboard.sales_scope === 'own' ? { own: 1 } : {}) }) : undefined;
  const pending = summary?.approvals;
  const staff = responses['staff-activity']?.data;
  const today = defaultPeriod(tenant.timezone).to;
  function preset(days) { const start = new Date(`${today}T12:00:00Z`); start.setUTCDate(start.getUTCDate() - days + 1); onFilters({ ...filters, from: start.toISOString().slice(0, 10), to: today }); }
  return <main className="content business-dashboard staff-dashboard staff-dashboard--manager">
    <PageHeader eyebrow={tenant.name} title="Branch Manager Dashboard" context={<DashboardContext scope={branch?.name || 'No branch selected'} salesScope={tenant.dashboard.sales_scope === 'own' ? 'Your sales only' : 'Branch activity'} currency={tenant.currency} period={filters}/>} description={branch ? `Performance and operations · ${branch.name}` : 'Select a permitted branch to review operations.'} updatedAt={updatedAt} timezone={tenant.timezone}
      actions={<><button className="secondary" onClick={onRefresh}><Icon name="refresh"/>Refresh dashboard</button>{allowed('approvals') && !noBranch && <a className="button-link" href="#pending-branch-approvals"><Icon name="check"/>Review approvals</a>}</>}/>
    <section className="card dashboard-controls" aria-label="Dashboard filters"><BranchSwitcher branches={tenant.allowed_branches} value={filters.branch_id || ''} onChange={branch_id => onFilters({ ...filters, branch_id, cash_session_id: undefined })}/>
      <DateRangeFilter value={filters} timezone={tenant.timezone} onApply={dates => onFilters({ ...filters, ...dates })}/></section>
    {noBranch ? <EmptyState title="No permitted branches" description="Ask an administrator to review your branch assignments before viewing branch operations."/> : <>
      <div className="staff-period-presets" aria-label="Quick reporting periods">{[[1, 'Today'], [7, 'Last 7 days'], [30, 'Last 30 days']].map(([days, label]) => <button className="secondary" key={days} onClick={() => preset(days)}><Icon name="calendar"/>{label}</button>)}</div>
      <p className="dashboard-scope">{branch?.name} · {tenant.dashboard.sales_scope === 'own' ? 'Your sales only' : 'Sales in this permitted branch'} · {tenant.currency}</p>
      <section className="dashboard-kpi-grid" aria-label="Branch performance indicators">{allowed('sales') && <>
        <KpiCard title="Branch net sales" value={summary?.sales.totals.net_sales_excluding_tax} currency={tenant.currency} format="money" icon="pos" period={filters} timezone={tenant.timezone} definition="Original invoices less posted return/cancellation credits, excluding net tax." to={report}/>
        <KpiCard title="Invoices issued" value={summary?.sales.invoice_count} icon="sales" period={filters} timezone={tenant.timezone} definition="Original invoices in this period, including subsequently voided invoices." to={salesLink}/>
        <KpiCard title="Average invoice value" value={summary?.sales.average_invoice_value_including_tax} currency={tenant.currency} format="money" icon="quote" period={filters} timezone={tenant.timezone} definition="Average original invoice value including tax, before return credits. Unavailable when no invoices exist." to={report}/>
        <KpiCard title="Posted return credits" value={summary?.sales.totals.return_credits} currency={tenant.currency} format="money" icon="returns" period={filters} timezone={tenant.timezone} definition="Returns and cancellations posted in this period, including tax and credits for older invoices." to={report}/>
      </>}</section>
      <div className="dashboard-detail-grid">{allowed('sales-trend') && <ChartCard title="Daily branch sales" description="Net sales excluding tax after posted credits." period={filters} timezone={tenant.timezone} unit={tenant.currency} error={errors['sales-trend']} onRetry={onRefresh}>
        <TrendChart title="Daily branch net sales excluding tax" currency={tenant.currency} unit={tenant.currency} data={responses['sales-trend']?.data.series.map(point => ({ label: point.date, value: point.net_sales_excluding_tax }))}/>{report && <Link to={report}>View branch report<Icon name="forward" size={16}/></Link>}</ChartCard>}
        {allowed('alerts') && <StockAlerts title="Branch stock alerts" response={responses.alerts} tenant={tenant} filters={filters} error={errors.alerts} onRefresh={onRefresh}/>}
      </div>
      <div className="dashboard-detail-grid">{allowed('approvals') && <PendingApprovals data={pending} tenant={tenant} title="Pending branch approvals" id="pending-branch-approvals"/>}
        <section className="card dashboard-actions-card"><h2>Branch operations</h2><QuickActionGrid title="Branch tools" permissions={tenant.permissions} actions={[
          { label: 'New sale', to: destination('/pos', { branch_id: filters.branch_id }), permission: 'sales.create', icon: 'pos' },
          { label: 'Review shifts', to: destination('/cash', { branch_id: filters.branch_id }), permission: 'cash.open', icon: 'cash' },
          { label: 'Stock balances', to: destination('/inventory', { branch_id: filters.branch_id }), permission: 'inventory.view', icon: 'inventory' },
          { label: 'Purchase orders', to: destination('/purchasing', { tab: 'orders', branch_id: filters.branch_id }), permission: 'purchases.view', icon: 'purchasing' },
        ]}/></section></div>
      {allowed('staff-activity') && <ChartCard title="Branch staff activity" description="Recorded sales/credits in this period and current open shifts. This is activity, not attendance or branch headcount." period={filters} timezone={tenant.timezone} unit={tenant.currency} error={errors['staff-activity']} onRetry={onRefresh}>
        {staff?.items.length ? <><TableViewport className="table-scroll"><table><caption className="sr-only">Scoped staff activity</caption><thead><tr><th scope="col">Staff</th><th className="numeric" scope="col">Invoices</th><th className="numeric" scope="col">Net sales excluding tax</th><th className="numeric" scope="col">Open shifts now</th></tr></thead><tbody>{staff.items.map(member => <tr key={member.membership_id}><th scope="row">{can('sales.view') ? <Link to={destination('/sales', { from: filters.from, to: filters.to, branch_id: filters.branch_id, cashier_membership_id: member.membership_id })}>{member.name}</Link> : member.name}</th><td className="numeric">{formatNumber(member.invoice_count)}</td><td className="money-text">{formatMoney(member.net_sales_excluding_tax, tenant.currency)}</td><td className="numeric">{formatNumber(member.open_sessions)}</td></tr>)}</tbody></table></TableViewport><Pagination value={staff.pagination} label="Staff activity results" onChange={page => onPage('staff', page)}/></> : <EmptyState title="No recorded staff activity" description="No invoices, posted credits or current open shifts in this branch scope."/>}
      </ChartCard>}
      {allowed('recent-sales') && <Receipts own={tenant.dashboard.sales_scope === 'own'} data={responses['recent-sales']?.data} error={errors['recent-sales']} tenant={tenant} period={filters} onRefresh={onRefresh} onPage={page => onPage('receipts', page)}/>}
      <p className="dashboard-metadata">Branch access comes from your current permissions and explicit assignments. Refresh reloads all widgets together. Stock, shifts and approvals are current snapshots. Staff shift counts require cash management access.</p>
    </>}
  </main>;
}
