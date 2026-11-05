// Presentation metadata only. Laravel remains the authorization boundary.
const toolsPermissions = ['settings.manage', 'support.manage', 'alerts.view', 'imports.manage', 'exports.manage', 'audit.view', 'legacy.manage'];
export function navigationFor(user) {
  const can = permission => user?.permissions?.includes(permission);
  const business = Boolean(user?.active_tenant);
  return [
    { to: '/', label: 'Dashboard', icon: 'overview', visible: can('dashboard.view'), end: true },
    { to: '/pos', label: 'Point of sale', icon: 'pos', visible: business && can('sales.create') },
    { to: '/sales', label: 'Sales & receipts', icon: 'sales', visible: business && can('sales.view') },
    { to: '/catalog', label: 'Products', icon: 'product', visible: business && can('products.view') },
    { to: '/inventory', label: 'Inventory', icon: 'inventory', visible: business && can('inventory.view') },
    { to: '/purchasing', label: 'Purchasing', icon: 'purchasing', visible: business && can('purchases.view') },
    { to: '/operations/customers', label: 'Customers', icon: 'customer', visible: business && can('customers.view') },
    { to: '/operations/suppliers', label: 'Suppliers', icon: 'businesses', visible: business && can('purchases.view') },
    { to: '/returns', label: 'Returns & exchanges', icon: 'returns', visible: business && can('sales.view') },
    { to: '/cash', label: 'Register shifts', icon: 'cash', visible: business && can('cash.open') },
    { to: '/expenses', label: 'Expenses', icon: 'expense', visible: business && can('expenses.view') },
    { to: '/reports', label: 'Reports', icon: 'reports', visible: business && can('reports.view') },
    { to: '/team', label: 'Team & roles', icon: 'team', visible: business && can('team.view') },
    { to: '/workspace', label: 'Businesses', icon: 'businesses', visible: true },
    { to: '/setup/categories', label: 'Catalog setup', icon: 'catalog', visible: business && can('products.view') },
    { to: '/setup/branches', label: 'Branches & locations', icon: 'branch', visible: business && can('products.view') && can('settings.manage') },
    { to: '/pos-settings', label: 'POS activation', icon: 'settings', visible: business && can('settings.manage') },
    { to: '/billing', label: 'Plans & billing', icon: 'billing', visible: business && can('billing.manage') },
    { to: '/legacy', label: 'Legacy mapping', icon: 'legacy', visible: business && user.active_tenant.is_legacy && can('legacy.view') },
    { to: '/workspace-tools', label: 'Workspace tools', icon: 'tools', visible: business && toolsPermissions.some(can) },
  ].filter(item => item.visible).map(item => ({ ...item, group: workspace.has(item.to) ? 'workspace' : operations.has(item.to) ? 'operations' : 'management' }));
}

const workspace = new Set(['/', '/pos', '/sales', '/cash']);
const operations = new Set(['/catalog', '/inventory', '/purchasing', '/operations/suppliers', '/returns']);
export function navigationSections(items) {
  return [['workspace', 'Workspace'], ['operations', 'Operations'], ['management', 'Management']]
    .map(([id, label]) => ({ id, label, items: items.filter(item => (item.group || 'workspace') === id) }))
    .filter(section => section.items.length);
}

export function pageTitle(pathname, items) {
  const match = [...items].sort((a, b) => b.to.length - a.to.length)
    .find(item => pathname === item.to || (!item.end && pathname.startsWith(`${item.to}/`)));
  if (match) return match.label;
  if (pathname.startsWith('/setup/')) return 'Catalog setup';
  if (pathname === '/pricing') return 'Plans & billing';
  return 'Workspace';
}
