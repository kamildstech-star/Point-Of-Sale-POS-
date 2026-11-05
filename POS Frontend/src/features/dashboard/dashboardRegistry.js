// Presentation and requests share this registry. The server remains the authority
// for effective grants, personal sales scope and permitted branches.
const any = (...permissions) => tenant => permissions.some(permission => tenant.permissions.includes(permission));
const all = (...permissions) => tenant => permissions.every(permission => tenant.permissions.includes(permission));
const company = tenant => tenant.dashboard.sales_scope !== 'own' && tenant.sales_scope !== 'own';
export const dashboardWidgets = {
  sales: { resource: 'summary', permits: any('sales.view', 'reports.view') },
  inventory: { resource: 'summary', permits: all('inventory.view') },
  purchasing: { resource: 'summary', permits: all('purchases.view') },
  accounting: { resource: 'summary', permits: tenant => company(tenant) && all('reports.view')(tenant) },
  shift: { resource: 'summary', permits: all('cash.open') },
  approvals: { resource: 'summary', permits: any('inventory.approve', 'purchases.approve', 'sales.return.approve', 'expenses.approve') },
  'sales-trend': { resource: 'sales-trend', permits: any('sales.view', 'reports.view') },
  'branch-performance': { resource: 'branch-performance', permits: tenant => company(tenant) && all('reports.view')(tenant) },
  'top-products': { resource: 'top-products', permits: tenant => all('products.view')(tenant) && any('sales.view', 'reports.view')(tenant) },
  'recent-sales': { resource: 'recent-sales', permits: all('sales.view') },
  'staff-activity': { resource: 'staff-activity', permits: tenant => company(tenant) && all('reports.view', 'team.view')(tenant) },
  alerts: { resource: 'alerts', permits: tenant => all('inventory.view')(tenant) && tenant.dashboard.allowed_widgets.includes('inventory') },
  cashier: { resource: 'cashier-summary', permits: any('cash.open', 'sales.view', 'sales.create') },
};

export function allowsWidget(tenant, widget) {
  return Boolean(tenant.permissions?.includes('dashboard.view') && tenant.dashboard?.allowed_widgets?.includes(widget) && dashboardWidgets[widget]?.permits(tenant));
}

const business = ['sales', 'inventory', 'purchasing', 'accounting', 'shift', 'approvals', 'sales-trend', 'branch-performance', 'top-products', 'alerts'];
const layouts = {
  owner: { title: 'Business Owner Dashboard', widgets: business },
  admin: { title: 'Business Admin Dashboard', widgets: business },
  manager: { title: 'Branch Manager Dashboard', widgets: ['sales', 'inventory', 'purchasing', 'shift', 'approvals', 'sales-trend', 'recent-sales', 'staff-activity', 'alerts'] },
  cashier: { title: 'Cashier Dashboard', widgets: ['cashier'] },
  accounting: { title: 'Accounting overview', widgets: ['accounting', 'sales', 'purchasing', 'inventory', 'approvals', 'shift', 'sales-trend', 'alerts'] },
  inventory: { title: 'Inventory workspace', widgets: ['inventory', 'purchasing', 'approvals', 'shift', 'sales', 'accounting', 'alerts', 'sales-trend'] },
  purchasing: { title: 'Purchasing workspace', widgets: ['purchasing', 'inventory', 'approvals', 'shift', 'sales', 'accounting', 'alerts', 'sales-trend'] },
  sales: { title: 'Sales workspace', widgets: ['sales', 'shift', 'inventory', 'purchasing', 'approvals', 'sales-trend', 'alerts'] },
  workspace: { title: 'Workspace overview', widgets: ['sales', 'inventory', 'purchasing', 'accounting', 'shift', 'approvals', 'sales-trend', 'alerts'] },
};

export function dashboardPlan(tenant) {
  // Profile codes only choose ordering. Never use role IDs/names as grants.
  let kind = ['owner', 'admin', 'manager', 'cashier'].includes(tenant.dashboard?.profile) ? tenant.dashboard.profile : 'workspace';
  if (kind === 'workspace') {
    if (allowsWidget(tenant, 'accounting')) kind = 'accounting';
    else if (allowsWidget(tenant, 'inventory') && tenant.permissions.includes('inventory.adjust')) kind = 'inventory';
    else if (allowsWidget(tenant, 'purchasing')) kind = 'purchasing';
    else if (allowsWidget(tenant, 'sales')) kind = 'sales';
    else if (allowsWidget(tenant, 'inventory')) kind = 'inventory';
  }
  const layout = layouts[kind];
  const widgets = layout.widgets.filter(widget => allowsWidget(tenant, widget));
  const resources = [...new Set(widgets.map(widget => dashboardWidgets[widget].resource))];
  // Summary is the primary snapshot when present; other failures stay local.
  resources.sort((a, b) => (a === 'summary' ? -1 : b === 'summary' ? 1 : 0));
  return { kind, title: layout.title, widgets, resources, has: widget => widgets.includes(widget) };
}
