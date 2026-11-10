import { dashboardFixture, fixtureTenant } from './businessDashboard.js';

// Synthetic contracts only. These fixtures never contact the working database.
export const specialistCases = {
  inventory: { title: 'Inventory workspace', permissions: ['dashboard.view', 'products.view', 'inventory.view', 'inventory.adjust'], widgets: ['inventory', 'alerts', 'inventory-trend'] },
  purchasing: { title: 'Purchasing workspace', permissions: ['dashboard.view', 'purchases.view', 'purchases.manage'], widgets: ['purchasing', 'purchase-orders', 'supplier-overview'] },
  accounting: { title: 'Accounting overview', permissions: ['dashboard.view', 'reports.view', 'sales.view', 'expenses.view', 'cash.open'], widgets: ['sales', 'accounting', 'sales-trend', 'accounting-trend', 'accounting-exceptions'] },
  associate: { title: 'Sales Associate Dashboard', profile: 'sales_associate', permissions: ['dashboard.view', 'sales.view', 'sales.create', 'products.view', 'customers.view', 'reports.view'], widgets: ['sales', 'accounting', 'sales-trend', 'personal-sales'] },
};
export function specialistTenant(kind) {
  const item = specialistCases[kind];
  return fixtureTenant({ permissions: item.permissions, roles: [{ id: 90, code: item.profile || kind, name: 'Synthetic ' + kind }], dashboard: { profile: item.profile || null, allowed_widgets: item.widgets, sales_scope: 'permitted_branches', specialist_enabled: true } });
}
export function specialistFixture(path, tenant) {
  if (path === 'me/context') return { data: { active_tenant: tenant } };
  const url = new URL(path, 'http://fixture.test'), resource = url.pathname.split('/').at(-1);
  const response = dashboardFixture(path.replace(/(inventory-trend|purchase-orders|supplier-overview|accounting-trend|accounting-exceptions|personal-sales)(\?|$)/, 'summary$2'), tenant);
  const page = Number(url.searchParams.get('page') || 1), date = response.meta.from;
  const pagination = { current_page: page, last_page: 2, per_page: 5, total: 6 };
  const receipt = { id: 31, invoice_no: 'SYNTH-OWN-31', sold_at: '2026-11-05T09:00:00Z', grand_total: '24.6900', currency_code: 'PKR', payment_status: 'paid' };
  if (resource === 'summary') {
    Object.assign(response.data.inventory, { recorded_skus: 24, quantities_by_unit: [{ unit_id: 1, unit: 'EA', on_hand: '9.0000', reserved: '0.0000', available: '9.0000' }, { unit_id: 2, unit: 'KG', on_hand: '1.2500', reserved: '0.0000', available: '1.2500' }] });
    Object.assign(response.data.purchasing, { status_counts: { draft: 1, approved: 4, partial: 2 }, suppliers_on_orders: 2, outstanding_payables: '75.5000' });
    Object.assign(response.data.accounting, { posted_expenses: '12.5000', external_collections: '24.6900', external_refunds: '1.0000', net_external_collections: '23.6900', exchange_credit_in: '0.0000', exchange_credit_out: '0.0000' });
  }
  if (resource === 'inventory-trend') response.data = { items: [{ unit_id: page === 1 ? 1 : 2, unit: page === 1 ? 'EA' : 'KG', series: [{ date, received: page === 1 ? '11.0000' : '1.2500', issued: page === 1 ? '2.0000' : '0.0000' }] }], pagination, adjustments: { draft: 1, posted: 3 }, transfers: null, basis: 'Posted movement quantities by business-local date. Adjustment counts are current snapshots.' };
  if (resource === 'purchase-orders') response.data = { items: [{ id: page === 1 ? 17 : 18, po_number: 'SYNTH-PO-' + page, supplier_name: 'Synthetic supplier', location_name: 'Main stock', status: 'approved', total: '123.4500' }], pagination, expected_deliveries: null, overdue_receipts: null };
  if (resource === 'supplier-overview') response.data = { items: [{ id: page, name: 'Synthetic supplier ' + page, orders: 3, open_orders: 2, outstanding_payables: '75.5000' }], pagination };
  if (resource === 'accounting-trend') response.data = { series: [{ date, net_sales_excluding_tax: '24.6900', posted_expenses: '12.5000' }], methods: [{ method_type: 'cash', collections: '24.6900', refunds: '1.0000', net_collections: '23.6900' }, { method_type: 'bank', collections: '10.0000', refunds: '0.0000', net_collections: '10.0000' }], bank_balance: null, net_profit: null, verified_gross_profit: null };
  if (resource === 'accounting-exceptions') response.data = { count: 6, items: [{ id: 5, closed_at: '2026-11-05T10:00:00Z', variance: '-1.5000' }], pagination, can_open_shifts: true };
  if (resource === 'personal-sales') { response.meta.sales_scope = 'own'; response.data = { sales: { totals: { net_sales_excluding_tax: '24.6900' }, invoice_count: 1 }, series: [{ date, net_sales_excluding_tax: '24.6900' }], receipts: { items: [receipt], pagination }, customers_on_invoices: 1, assigned_customers: null, follow_ups: null, basis: 'Your invoices only, including credits posted in the reporting period.' }; }
  return response;
}
export function platformFixture(path, userId = 83) {
  const query = new URL(path, 'http://fixture.test').searchParams;
  return { data: { tenants: { total: 12, non_archived: 10, statuses: { active: 10, archived: 2 } }, subscriptions: { active: 7, trialing: 2, past_due: 1 }, tenant_growth: [{ date: query.get('from'), created: 2 }], incidents: { failed_billing_events: 1, pending_billing_events: 2, unpaid_provider_invoices: 3, failed_queue_jobs: null }, mrr: null, mrr_unavailable_reason: 'Authoritative recurring amounts are unavailable; provisional prices are not revenue.', basis: 'Aggregate snapshots only. No tenant identities or provider payloads.' }, meta: { scope: 'platform', user_id: userId, from: query.get('from'), to: query.get('to'), timezone: 'UTC', generated_at: '2026-11-05T10:00:00Z' } };
}
