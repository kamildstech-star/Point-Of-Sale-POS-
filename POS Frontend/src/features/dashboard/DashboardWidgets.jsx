import { Link } from 'react-router-dom';
import Icon from '../../components/Icon';
import TableViewport from '../../components/ui/TableViewport';
import KpiCard from '../../components/dashboard/KpiCard';
import ChartCard from '../../components/dashboard/ChartCard';
import AlertList from '../../components/dashboard/AlertList';
import EmptyState from '../../components/dashboard/EmptyState';
import { formatNumber, formatMoney } from '../../components/dashboard/format';
import { approvalDestination, approvalTypes, destination } from './dashboardData';

export function PendingApprovals({ data, tenant, title = 'Pending approvals', id }) {
  return <section className="card dashboard-approvals" id={id} aria-label={title}><h2>{title}</h2><p className="muted">Current actionable documents in this scope. Up to five latest records per type.</p>
    <dl className="dashboard-counts">{Object.entries(data?.counts || {}).map(([type, count]) => <div key={type}><dt>{approvalTypes[type]?.name || type}</dt><dd>{formatNumber(count)}</dd></div>)}</dl>
    {data?.items?.length ? <ul className="dashboard-approval-list">{data.items.map(item => { const to = approvalDestination(item, tenant.permissions), name = approvalTypes[item.type]?.name || item.type; return <li key={`${item.type}:${item.id}`}><div><strong>{name} #{item.id}</strong><small>{item.status}</small></div>{to ? <Link to={to} aria-label={`Review ${name.toLowerCase()} ${item.id}`}>Review<Icon name="forward" size={14}/></Link> : <span className="muted">Source access required</span>}</li>; })}</ul>
      : data ? <EmptyState title={title === 'Pending branch approvals' ? 'No pending branch approvals' : 'No pending approvals'} description="No actionable documents in your current scope."/> : <EmptyState title="Approvals unavailable" description="Refresh to request a current snapshot."/>}
  </section>;
}

export function StockAlerts({ response, error, tenant, filters, onRefresh, title = 'Stock alerts' }) {
  const alerts = response?.data.items.map(item => ({ ...item, title: item.type === 'out_of_stock' ? 'Out of stock' : 'Low stock',
    message: `${item.product_name || item.sku} · ${item.sku} · ${item.location_name}. Available: ${formatNumber(item.available)} ${item.unit || ''}.`,
    to: tenant.permissions.includes('inventory.view') ? destination('/inventory', { tab: 'balances', branch_id: filters.branch_id, location_id: item.location_id, variant_id: item.variant_id }) : undefined,
  }));
  return <AlertList title={title} alerts={alerts} timezone={tenant.timezone} error={error} onRetry={onRefresh}/>;
}

export function SummaryIndicators({ state, secondary = false }) {
  const { tenant, filters, responses, plan } = state;
  const data = responses.summary?.data || {}, scope = filters.branch_id ? tenant.allowed_branches.find(branch => String(branch.id) === String(filters.branch_id))?.name : 'All permitted branches';
  const report = plan.has('accounting') ? destination('/reports', filters) : undefined;
  const inventory = query => destination('/inventory', { branch_id: filters.branch_id, ...query });
  const purchasing = query => destination('/purchasing', { branch_id: filters.branch_id, tab: 'orders', ...query });
  const current = { scope: `Current snapshot · ${scope}` };
  const period = { period: filters, timezone: tenant.timezone, scope };
  const metrics = {
    inventory: [
      { title: 'Recorded SKUs', value: data.inventory?.recorded_skus, icon: 'product', definition: 'Distinct SKUs with recorded balances in permitted stock locations, not all catalog products.', to: inventory({ tab: 'balances' }) },
      { title: 'Low stock locations', value: data.inventory?.low_stock_locations, icon: 'warning', definition: data.inventory?.low_stock_threshold == null ? 'Configure a low stock threshold in Workspace tools. An unset threshold is unavailable.' : `SKU-location pairs above zero and at or below ${data.inventory.low_stock_threshold} available units.`, to: data.inventory?.low_stock_threshold == null ? undefined : inventory({ tab: 'balances', stock_state: 'low' }) },
      { title: 'Out of stock locations', value: data.inventory?.out_of_stock_locations, icon: 'inventory', definition: 'Recorded SKU-location pairs with available quantity at or below zero.', to: inventory({ tab: 'balances', stock_state: 'out' }) },
      { title: 'Draft stock counts', value: data.inventory?.pending_counts, icon: 'inventory', definition: 'Current draft stock counts in your permitted locations.' },
    ].map(metric => ({ ...current, ...metric })),
    purchasing: [
      { title: 'Open purchase orders', value: data.purchasing?.open_orders, icon: 'purchasing', definition: 'Approved or partially received purchase orders.', to: purchasing({ status: 'open' }) },
      { title: 'Draft purchase orders', value: data.purchasing?.draft_orders, icon: 'purchasing', definition: 'Draft purchase orders awaiting completion or approval.', to: purchasing({ status: 'draft' }) },
      { title: 'Suppliers on orders', value: data.purchasing?.suppliers_on_orders, icon: 'purchasing', definition: 'Distinct suppliers on current permitted purchase documents; not the full supplier directory.', to: purchasing({}) },
      { title: 'Outstanding supplier payables', value: data.purchasing?.outstanding_payables, format: 'money', icon: 'billing', definition: 'Current outstanding balances on permitted supplier payables.', to: tenant.permissions.includes('purchases.settle') ? destination('/purchasing', { branch_id: filters.branch_id, tab: 'payables' }) : undefined },
    ].map(metric => ({ ...current, ...metric })),
    accounting: [
      { ...period, title: 'Net external collections', value: data.accounting?.net_external_collections, format: 'money', icon: 'cash', definition: 'Recorded external payments less refunds in this period, including older invoices. Exchange credits are separate.', to: report },
      { ...current, title: 'Current receivables', value: data.accounting?.current_balances?.receivables, format: 'money', icon: 'billing', definition: 'Current lifetime balances due on permitted sales.', to: report },
      { ...period, title: 'Posted expenses', value: data.accounting?.posted_expenses, format: 'money', icon: 'expense', definition: 'Posted expense amounts in this reporting period. Revenue less expenses is not net profit.' },
      { ...current, title: 'Current refunds due', value: data.accounting?.current_balances?.refunds_due, format: 'money', icon: 'returns', definition: 'Current outstanding refund balances on permitted invoices.', to: report },
    ],
    sales: [
      { ...period, title: tenant.dashboard.sales_scope === 'own' ? 'Your net sales excluding tax' : 'Net sales excluding tax', value: data.sales?.totals.net_sales_excluding_tax, format: 'money', icon: 'pos', definition: 'Original invoices less posted return/cancellation credits and net tax.', to: report },
      { ...period, title: 'Invoices issued', value: data.sales?.invoice_count, icon: 'sales', definition: 'Original invoices in this period, including subsequently voided invoices.', to: tenant.permissions.includes('sales.view') ? destination('/sales', { ...filters, ...(tenant.dashboard.sales_scope === 'own' ? { own: 1 } : {}) }) : undefined },
    ],
    shift: [{ ...current, title: 'Open shifts', value: data.shift?.open_sessions, icon: 'cash', definition: `Current open sessions. ${data.shift?.scope === 'own' ? 'Your sessions only.' : 'Permitted sessions.'}`, to: destination('/cash', { branch_id: filters.branch_id, ...(data.shift?.scope === 'own' ? { own: 1 } : {}) }) }],
  };
  const order = plan.kind === 'accounting' ? ['accounting', 'sales', 'purchasing', 'inventory', 'shift'] : plan.kind === 'purchasing' ? ['purchasing', 'inventory', 'sales', 'shift', 'accounting'] : plan.kind === 'inventory' ? ['inventory', 'purchasing', 'sales', 'shift', 'accounting'] : ['sales', 'shift', 'inventory', 'purchasing', 'accounting'];
  const cards = order.filter(widget => plan.has(widget)).flatMap(widget => metrics[widget]);
  const visible = secondary ? cards.slice(4) : cards.slice(0, 4);
  if (!visible.length) return null;
  return <section className={`dashboard-kpi-grid${secondary ? ' dashboard-secondary-metrics' : ''}`} aria-label={secondary ? 'Additional workspace indicators' : 'Key performance indicators'}>{visible.map(metric => <KpiCard key={metric.title} currency={tenant.currency} {...metric}/>)}</section>;
}

export function StockQuantities({ data }) {
  return <ChartCard title="Stock quantities by unit" description="Current recorded balances. Different units are never added together.">
    {data?.quantities_by_unit?.length ? <TableViewport className="table-scroll"><table><caption className="sr-only">Current stock quantities by unit</caption><thead><tr><th scope="col">Unit</th><th scope="col" className="numeric">On hand</th><th scope="col" className="numeric">Reserved</th><th scope="col" className="numeric">Available</th></tr></thead><tbody>{data.quantities_by_unit.map(unit => <tr key={unit.unit_id}><th scope="row">{unit.unit}</th><td className="numeric">{formatNumber(unit.on_hand)}</td><td className="numeric">{formatNumber(unit.reserved)}</td><td className="numeric">{formatNumber(unit.available)}</td></tr>)}</tbody></table></TableViewport> : <EmptyState title={data ? 'No recorded stock balances' : 'Stock quantities unavailable'}/>}
  </ChartCard>;
}

export function AccountingBasis({ data, tenant, period }) {
  return <ChartCard title="Collections and refunds" description="Recorded period amounts, separate from revenue. Exchange credit is not external cash." period={period} timezone={tenant.timezone} unit={tenant.currency}>
    <dl className="dashboard-counts">{[['External collections', data?.external_collections], ['External refunds', data?.external_refunds], ['Exchange credit in', data?.exchange_credit_in], ['Exchange credit out', data?.exchange_credit_out]].map(([name, amount]) => <div key={name}><dt>{name}</dt><dd className="money-text">{formatMoney(amount, tenant.currency) ?? 'Unavailable'}</dd></div>)}</dl>
    <p className="dashboard-metadata">Verified gross profit, net profit and bank balances are unavailable. No estimate is substituted.</p>
  </ChartCard>;
}
