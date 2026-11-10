import { useState } from 'react';
import { Link } from 'react-router-dom';
import ChartCard from '../../components/dashboard/ChartCard';
import TrendChart from '../../components/dashboard/TrendChart';
import EmptyState from '../../components/dashboard/EmptyState';
import TableViewport from '../../components/ui/TableViewport';
import Icon from '../../components/Icon';
import { formatMoney, formatNumber, formatUpdatedAt } from '../../components/dashboard/format';
import { destination } from './dashboardData';

export function DashboardPagination({ data, label, onPage }) {
  if (!data || data.last_page <= 1) return null;
  return <nav className="dashboard-pagination" aria-label={label}><button className="secondary" disabled={data.current_page <= 1} onClick={() => onPage(data.current_page - 1)}><Icon name="previous"/>Previous</button><span role="status" aria-live="polite">Page {data.current_page} of {data.last_page} · {data.total} records</span><button className="secondary" disabled={data.current_page >= data.last_page} onClick={() => onPage(data.current_page + 1)}>Next<Icon name="next"/></button></nav>;
}

function PairedTrend({ data, first, second, unit, currency }) {
  return <div className="dashboard-series-grid">{[first, second].map(series => <section key={series.key} aria-label={series.label}><h3>{series.label}</h3><TrendChart title={series.label} unit={unit} currency={currency} data={data?.map(point => ({ label: point.date, value: point[series.key] }))}/></section>)}</div>;
}

function InventoryActivity({ state, onRefresh, onPage }) {
  const { tenant, filters, responses, errors } = state, data = responses['inventory-trend']?.data;
  const [selected, setSelected] = useState('');
  const unit = data?.items.find(item => String(item.unit_id) === selected) || data?.items[0];
  return <ChartCard title="Stock movement" description="Posted stock received and issued, separated by base unit." period={filters} timezone={tenant.timezone} error={errors['inventory-trend']} onRetry={onRefresh}>
    {data?.items.length ? <><label>Movement unit<select value={String(unit.unit_id)} onChange={event => setSelected(event.target.value)}>{data.items.map(item => <option key={item.unit_id} value={item.unit_id}>{item.unit}</option>)}</select></label>
      <PairedTrend data={unit.series} first={{ key: 'received', label: 'Stock received' }} second={{ key: 'issued', label: 'Stock issued' }} unit={unit.unit}/>
      <DashboardPagination data={data.pagination} label="Movement unit results" onPage={page => onPage('units', page)}/>
    </> : <EmptyState title="No posted stock movement" description="No units have movement in the permitted locations and reporting dates."/>}
    {data && <><h3>Stock adjustment status</h3><dl className="dashboard-counts">{Object.entries(data.adjustments).map(([status, count]) => <div key={status}><dt>{status}</dt><dd>{formatNumber(count)}</dd></div>)}</dl><p className="dashboard-metadata">{data.basis}</p></>}
  </ChartCard>;
}

function OpenOrders({ state, onRefresh, onPage }) {
  const { tenant, filters, responses, errors } = state, data = responses['purchase-orders']?.data;
  const status = responses.summary?.data.purchasing?.status_counts;
  return <ChartCard title="Purchase order status and open orders" description="Current snapshot: approved or partially received orders." timezone={tenant.timezone} unit={tenant.currency} error={errors['purchase-orders']} onRetry={onRefresh}>
    {status && Object.keys(status).length > 0 && <dl className="dashboard-counts">{Object.entries(status).map(([name, count]) => <div key={name}><dt>{name}</dt><dd>{formatNumber(count)}</dd></div>)}</dl>}
    {data?.items.length ? <TableViewport label="Open purchase orders"><table><caption className="sr-only">Open purchase orders in your current scope</caption><thead><tr><th scope="col">Order</th><th scope="col">Supplier / location</th><th scope="col">Status</th><th scope="col" className="numeric">Total</th></tr></thead><tbody>{data.items.map(order => <tr key={order.id}><th scope="row"><Link to={destination('/purchasing', { tab: 'orders', branch_id: filters.branch_id, document_id: order.id })}>{order.po_number}</Link></th><td>{order.supplier_name}<small className="dashboard-sku">{order.location_name}</small></td><td>{order.status}</td><td className="money-text">{formatMoney(order.total, tenant.currency)}</td></tr>)}</tbody></table></TableViewport> : <EmptyState title="No open purchase orders"/>}
    <DashboardPagination data={data?.pagination} label="Open order results" onPage={page => onPage('orders', page)}/>
    <p className="dashboard-metadata">Expected delivery dates and overdue receipts are unavailable: purchase orders do not store delivery dates.</p>
  </ChartCard>;
}

function Suppliers({ state, onRefresh, onPage }) {
  const { tenant, responses, errors } = state, data = responses['supplier-overview']?.data;
  return <ChartCard title="Suppliers on purchase orders" description="Current activity and payables in your permitted locations." timezone={tenant.timezone} unit={tenant.currency} error={errors['supplier-overview']} onRetry={onRefresh}>
    {data?.items.length ? <TableViewport label="Supplier activity"><table><caption className="sr-only">Suppliers on permitted orders</caption><thead><tr><th scope="col">Supplier</th><th scope="col" className="numeric">Orders</th><th scope="col" className="numeric">Open</th><th scope="col" className="numeric">Outstanding</th></tr></thead><tbody>{data.items.map(supplier => <tr key={supplier.id}><th scope="row">{supplier.name}</th><td className="numeric">{formatNumber(supplier.orders)}</td><td className="numeric">{formatNumber(supplier.open_orders)}</td><td className="money-text">{formatMoney(supplier.outstanding_payables, tenant.currency)}</td></tr>)}</tbody></table></TableViewport> : <EmptyState title="No suppliers on permitted orders"/>}
    <DashboardPagination data={data?.pagination} label="Supplier activity results" onPage={page => onPage('suppliers', page)}/>
  </ChartCard>;
}

function AccountingActivity({ state, onRefresh }) {
  const { tenant, filters, responses, errors } = state, data = responses['accounting-trend']?.data;
  return <ChartCard title="Revenue and expenses" description="Recognized net sales excluding tax and posted expenses. Their difference is not net profit." period={filters} timezone={tenant.timezone} unit={tenant.currency} error={errors['accounting-trend']} onRetry={onRefresh}>
    <PairedTrend data={data?.series} first={{ key: 'net_sales_excluding_tax', label: 'Net sales excluding tax' }} second={{ key: 'posted_expenses', label: 'Posted expenses' }} unit={tenant.currency} currency={tenant.currency}/>
    {data?.methods.length > 0 && <TableViewport label="Recorded payment flows"><table><caption>Recorded payment flows, including older invoices</caption><thead><tr><th scope="col">Method type</th><th scope="col" className="numeric">Collections</th><th scope="col" className="numeric">Refunds</th><th scope="col" className="numeric">Net recorded</th></tr></thead><tbody>{data.methods.map(method => <tr key={method.method_type}><th scope="row">{method.method_type}{method.method_type === 'exchange' && ' (credit)'}</th><td className="money-text">{formatMoney(method.collections, tenant.currency)}</td><td className="money-text">{formatMoney(method.refunds, tenant.currency)}</td><td className="money-text">{formatMoney(method.net_collections, tenant.currency)}</td></tr>)}</tbody></table></TableViewport>}
    <p className="dashboard-metadata">Bank balances, verified profit and full bank reconciliation are unavailable. Recorded bank payments are payment flows.</p>
  </ChartCard>;
}

function AccountingExceptions({ state, onRefresh, onPage }) {
  const { tenant, filters, responses, errors } = state, data = responses['accounting-exceptions']?.data;
  return <ChartCard title="Cash variance exceptions" description="Closed shifts with a recorded non-zero variance; this is not a full ledger reconciliation." period={filters} timezone={tenant.timezone} unit={tenant.currency} error={errors['accounting-exceptions']} onRetry={onRefresh}>
    {data && <p><strong>{formatNumber(data.count)}</strong> closed shifts with variance in this period.</p>}
    {data?.items.length > 0 && <TableViewport label="Cash variance records"><table><caption className="sr-only">Closed shifts requiring review</caption><thead><tr><th scope="col">Shift</th><th scope="col">Closed at</th><th scope="col" className="numeric">Variance</th></tr></thead><tbody>{data.items.map(shift => <tr key={shift.id}><th scope="row"><Link to={destination('/cash', { branch_id: filters.branch_id, document_id: shift.id })}>Shift #{shift.id}</Link></th><td>{formatUpdatedAt(shift.closed_at, tenant.timezone)}</td><td className="money-text">{formatMoney(shift.variance, tenant.currency)}</td></tr>)}</tbody></table></TableViewport>}
    {data && !data.count && <EmptyState title="No recorded cash variance exceptions"/>}
    {data && !data.can_open_shifts && <p className="dashboard-metadata">Shift documents require register-management access; only the aggregate count is available.</p>}
    <DashboardPagination data={data?.pagination} label="Cash exception results" onPage={page => onPage('exceptions', page)}/>
  </ChartCard>;
}

export default function SpecialistWidgets(props) {
  const { plan } = props.state;
  return <section className="dashboard-section specialist-widgets" aria-label="Specialist activity">
    {plan.has('inventory-trend') && <InventoryActivity {...props}/>}
    <div className="dashboard-detail-grid">{plan.has('purchase-orders') && <OpenOrders {...props}/>}{plan.has('supplier-overview') && <Suppliers {...props}/>}</div>
    {plan.has('accounting-trend') && <AccountingActivity {...props}/>}
    {plan.has('accounting-exceptions') && <AccountingExceptions {...props}/>}
    {plan.has('inventory-trend') && <p className="dashboard-metadata">Stock transfers are unavailable. Use the supported stock adjustment and count workflows.</p>}
  </section>;
}
