// Synthetic API contract fixtures used only by automated tests and previews.
export const dashboardPermissions = ['dashboard.view', 'sales.view', 'reports.view', 'products.view', 'products.manage', 'sales.create', 'inventory.view', 'inventory.adjust', 'inventory.approve', 'purchases.view', 'purchases.approve', 'cash.open', 'cash.manage', 'expenses.view', 'expenses.approve', 'sales.return.approve', 'team.view'];
export function fixtureTenant(overrides = {}) {
  return { id: 1, name: 'Synthetic Retail', currency: 'PKR', timezone: 'Asia/Karachi', membership_id: 7,
    access_fingerprint: 'test-policy-1', permissions: dashboardPermissions, roles: [{ id: 1, code: 'owner', name: 'Owner' }], branch_scope: 'all', sales_scope: 'all',
    allowed_branches: [{ id: 1, code: 'MAIN', name: 'Main Store' }, { id: 2, code: 'CITY', name: 'City Branch' }],
    dashboard: { profile: 'owner', sales_scope: 'permitted_branches', allowed_widgets: ['sales', 'sales-trend', 'branch-performance', 'top-products', 'inventory', 'purchasing', 'shift', 'accounting', 'approvals', 'alerts'] }, ...overrides };
}
export function dashboardFixture(path, tenant = fixtureTenant(), options = {}) {
  if (path === 'me/context') return { data: { active_tenant: tenant } };
  const url = new URL(path, 'http://fixture.test/');
  const branch = url.searchParams.get('branch_id');
  const net = branch === '1' ? '90.0000' : branch === '2' ? '-12.3500' : '77.6500';
  const meta = { tenant_id: tenant.id, access_fingerprint: tenant.access_fingerprint, from: url.searchParams.get('from'), to: url.searchParams.get('to'),
    branch_ids: branch ? [Number(branch)] : tenant.allowed_branches.map(b => b.id), currency: tenant.currency, timezone: tenant.timezone,
    sales_scope: tenant.dashboard.sales_scope, data_revision: options.revision ?? 1, generated_at: '2026-11-03T10:00:00Z', max_cache_age_seconds: 30, includes_unassigned_locations: !branch };
  const sales = { totals: { net_sales_excluding_tax: net, return_credits: '12.3500' }, invoice_count: 3 };
  const data = {
    summary: { capabilities: tenant.dashboard,
      sales, inventory: { low_stock_locations: 2, low_stock_threshold: '3', out_of_stock_locations: 1 },
      shift: { open_sessions: 2, scope: 'permitted_branches' }, purchasing: { open_orders: 3 },
      accounting: { current_balances: { receivables: '15.0000' }, net_external_collections: '60.0000' },
      approvals: { counts: { 'stock-adjustment': 1, 'purchase-order': 1, expense: 1 }, items: [{ type: 'stock-adjustment', id: 12, status: 'draft' }, { type: 'purchase-order', id: 17, status: 'draft' }, { type: 'expense', id: 6, status: 'draft' }] },
      comparison: { from: '2026-09-06', to: '2026-10-05', sales: { totals: { net_sales_excluding_tax: '0.0000' } }, change_percent: { net_sales_excluding_tax: null } },
    },
    'sales-trend': { series: [{ date: meta.from, net_sales_excluding_tax: net }] },
    'branch-performance': { items: tenant.allowed_branches.filter(b => !branch || String(b.id) === branch).map(b => ({ ...b, net_sales_excluding_tax: b.id === 1 ? '90.0000' : '-12.3500' })), pagination: { current_page: Number(url.searchParams.get('page') || 1), last_page: 1, per_page: 5, total: tenant.allowed_branches.length } },
    'top-products': { items: [{ variant_id: 4, product_id: 8, name: 'Cotton shirt', sku: 'SYNTH-SHIRT-M', net_units: '5.0000', unit: 'EA', net_sales_excluding_tax: net }] },
    alerts: { items: [{ id: 'stock:4', variant_id: 4, location_id: 3, sku: 'SYNTH-SHIRT-M', product_name: 'Cotton shirt', location_name: 'Main stock', unit: 'EA', type: 'low_stock', severity: 'warning', available: '2.0000', updated_at: meta.generated_at }] },
  }[url.pathname.split('/').at(-1)];
  if (!data) throw new Error(`Missing synthetic dashboard fixture: ${path}`);
  return { data, meta };
}
