// Dates are calendar dates in the business timezone, not browser-local midnights.
export const dashboardError = message => Object.assign(new Error(message), { response: { data: { message } } });

export function sameDashboardFilters(first = {}, second = {}) {
  const canonical = values => JSON.stringify(Object.entries(values).filter(([, value]) => value !== '' && value !== undefined && value !== null)
    .map(([name, value]) => [name, String(value)]).sort(([a], [b]) => a.localeCompare(b)));
  return canonical(first) === canonical(second);
}

export function defaultPeriod(timezone, now = new Date()) {
  const parts = Object.fromEntries(new Intl.DateTimeFormat('en-US', { timeZone: timezone, year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(now).map(part => [part.type, part.value]));
  const to = `${parts.year}-${parts.month}-${parts.day}`;
  const start = new Date(`${to}T12:00:00Z`); start.setUTCDate(start.getUTCDate() - 29);
  return { from: start.toISOString().slice(0, 10), to };
}

export function destination(path, filters = {}) {
  const query = new URLSearchParams(Object.entries(filters).filter(([, value]) => value !== '' && value !== undefined && value !== null));
  return `${path}${query.size ? `?${query}` : ''}`;
}

export const approvalTypes = {
  'stock-adjustment': { name: 'Stock adjustments', permission: 'inventory.view', path: '/inventory', tab: 'adjustments' },
  'stock-count': { name: 'Stock counts', permission: 'inventory.view', path: '/inventory', tab: 'counts' },
  'purchase-order': { name: 'Purchase orders', permission: 'purchases.view', path: '/purchasing', tab: 'orders' },
  'sale-return': { name: 'Returns', permission: 'sales.view', path: '/returns' },
  expense: { name: 'Expenses', permission: 'expenses.view', path: '/expenses' },
};

export function approvalDestination(item, permissions) {
  const type = approvalTypes[item.type];
  if (!type || !permissions.includes(type.permission)) return null;
  return item.type === 'sale-return' ? `/returns/${item.id}` : destination(type.path, { tab: type.tab, document_id: item.id });
}

// A response from an older request/policy/business must never be combined with
// the selected view. Financial arithmetic remains exclusively on the server.
export function verifySnapshot(response, tenant, filters, salesScope = tenant.dashboard.sales_scope) {
  const meta = response?.meta;
  if (!meta || String(meta.tenant_id) !== String(tenant.id) || meta.access_fingerprint !== tenant.access_fingerprint ||
    meta.from !== filters.from || meta.to !== filters.to || meta.currency !== tenant.currency || meta.timezone !== tenant.timezone ||
    meta.sales_scope !== salesScope ||
    JSON.stringify(meta.branch_ids.map(String).sort()) !== JSON.stringify((filters.branch_id ? [filters.branch_id] : tenant.allowed_branches.map(branch => branch.id)).map(String).sort())) {
    throw dashboardError('Your dashboard access or reporting scope changed. Refresh the dashboard to load the current view.');
  }
}
