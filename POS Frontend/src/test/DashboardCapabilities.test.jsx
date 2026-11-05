import { cleanup, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import WorkspaceOverview from '../features/dashboard/WorkspaceOverview';
import { dashboardPlan } from '../features/dashboard/dashboardRegistry';
import { workspaceApi } from '../api/workspace';
import { dashboardFixture, fixtureTenant } from './fixtures/businessDashboard';

let tenant, staff;
vi.mock('../auth/AuthProvider', () => ({ useAuth: () => ({ user: staff }) }));
vi.mock('../api/workspace', () => ({ workspaceApi: { get: vi.fn() } }));
const open = () => render(<MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}><WorkspaceOverview/></MemoryRouter>);
const ready = title => screen.findByRole('heading', { name: title }, { timeout: 10000 });
const card = title => screen.getByRole('heading', { name: title }).closest('article');
const paths = () => workspaceApi.get.mock.calls.map(([path]) => path.split('?')[0]).filter(path => path.startsWith('dashboard/'));
function fixture(path) {
  const result = dashboardFixture(path, tenant);
  if (path.startsWith('dashboard/summary')) {
    Object.assign(result.data.inventory, { recorded_skus: 28, pending_counts: 3, quantities_by_unit: [{ unit_id: 1, unit: 'EA', on_hand: '12345678901234567890.1234', reserved: '2.0000', available: '12345678901234567888.1234' }, { unit_id: 2, unit: 'KG', on_hand: '3.5000', reserved: '0.0000', available: '3.5000' }] });
    Object.assign(result.data.purchasing, { draft_orders: 2, suppliers_on_orders: 4, outstanding_payables: '12345678901234567890.1234' });
    Object.assign(result.data.accounting, { external_collections: '21.00', external_refunds: '1.00', posted_expenses: '12.00', exchange_credit_in: '3.00', exchange_credit_out: '2.00', current_balances: { receivables: '15.00', refunds_due: '2.00' } });
    for (const key of ['sales', 'inventory', 'purchasing', 'accounting', 'shift', 'approvals']) if (!tenant.dashboard.allowed_widgets.includes(key)) delete result.data[key];
  }
  return result;
}
function grants(permissions, widgets) {
  tenant.permissions = ['dashboard.view', ...permissions];
  tenant.dashboard = { ...tenant.dashboard, profile: null, sales_scope: permissions.includes('reports.view') ? 'permitted_branches' : 'own', allowed_widgets: widgets };
  staff.permissions = tenant.permissions;
}
beforeEach(() => {
  vi.resetAllMocks(); tenant = fixtureTenant();
  staff = { id: 4, name: 'Custom Staff', active_tenant: { id: 1 }, permissions: tenant.permissions, role: { id: 1, name: 'Owner' } };
  workspaceApi.get.mockImplementation(async path => fixture(path));
});
afterEach(cleanup);

describe('UX4 capability composition', () => {
  it('prioritizes inventory exceptions and keeps mixed units and exact decimals separate', async () => {
    grants(['inventory.view', 'inventory.adjust', 'products.view'], ['inventory', 'alerts']); open(); await ready('Inventory workspace');
    expect(within(card('Recorded SKUs')).getByText('28')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Draft stock counts' })).toBeInTheDocument();
    expect(screen.getByText('12,345,678,901,234,567,888.1234')).toBeInTheDocument();
    expect(screen.getByRole('rowheader', { name: 'KG' })).toBeInTheDocument();
    expect(screen.queryByLabelText('From date')).not.toBeInTheDocument();
    expect(paths()).toEqual(['dashboard/summary', 'dashboard/alerts']);
    expect(screen.getByRole('region', { name: 'Action required' }).compareDocumentPosition(screen.getByRole('heading', { name: 'Quick actions' })) & Node.DOCUMENT_POSITION_FOLLOWING).toBeTruthy();
  });
  it('shows purchasing counts and payables without fabricated delivery metrics', async () => {
    grants(['purchases.view', 'purchases.manage'], ['purchasing']); open(); await ready('Purchasing workspace');
    expect(within(card('Outstanding supplier payables')).getByText(/12,345,678,901,234,567,890\.12/)).toBeInTheDocument();
    expect(within(card('Open purchase orders')).getByRole('link')).toHaveAttribute('href', '/purchasing?tab=orders&status=open');
    expect(screen.queryByText('Expected deliveries')).not.toBeInTheDocument();
    expect(paths()).toEqual(['dashboard/summary']);
  });
  it('separates accounting collections, expenses and current balances from unsupported profit', async () => {
    grants(['reports.view', 'sales.view', 'expenses.view'], ['accounting', 'sales', 'sales-trend', 'branch-performance']); open(); await ready('Accounting overview');
    expect(screen.getByRole('heading', { name: 'Collections and refunds' })).toBeInTheDocument();
    expect(screen.getByText(/Verified gross profit, net profit and bank balances are unavailable/)).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Current refunds due' })).toBeInTheDocument();
    expect(paths()).toEqual(['dashboard/summary', 'dashboard/sales-trend']);
  });
  it('gives viewers permitted tools without requesting hidden dashboard data', async () => {
    grants(['products.view'], []); open(); await ready('Workspace overview');
    expect(screen.getByText('No dashboard metrics available')).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /Find product/ })).toHaveAttribute('href', '/catalog');
    expect(paths()).toEqual([]); expect(screen.queryByRole('article')).not.toBeInTheDocument();
  });
  it('uses the union of custom role grants without relying on numeric role IDs', async () => {
    grants(['inventory.view', 'inventory.adjust', 'purchases.view', 'sales.view'], ['inventory', 'purchasing', 'sales', 'sales-trend', 'alerts']);
    tenant.roles = [{ id: 999, code: 'stock_custom' }, { id: 1, code: 'buying_custom' }]; open(); await ready('Inventory workspace');
    expect(screen.getByRole('heading', { name: 'Open purchase orders' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Your net sales excluding tax' })).toBeInTheDocument();
  });
  it('does not accept a capability without the corresponding effective grant', async () => {
    grants(['products.view'], ['sales', 'accounting', 'inventory', 'purchasing', 'shift', 'approvals', 'alerts', 'sales-trend']); open(); await ready('Workspace overview');
    expect(paths()).toEqual([]); expect(screen.queryByRole('article')).not.toBeInTheDocument();
  });
  it('preserves personal scope and omits company reporting despite an outdated capability list', async () => {
    grants(['sales.view', 'reports.view'], ['sales', 'accounting', 'sales-trend', 'branch-performance']);
    tenant.dashboard.sales_scope = 'own'; tenant.sales_scope = 'own'; open(); await ready('Sales workspace');
    expect(within(card('Invoices issued')).getByRole('link')).toHaveAttribute('href', expect.stringContaining('own=1'));
    expect(screen.queryByRole('heading', { name: 'Current receivables' })).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /View reports/ })).not.toBeInTheDocument();
    expect(paths()).toEqual(['dashboard/summary', 'dashboard/sales-trend']);
  });
  it('uses verified fresh grants when stale auth still names an owner', async () => {
    grants(['inventory.view'], ['inventory', 'alerts']); staff.permissions = fixtureTenant().permissions;
    open(); await ready('Inventory workspace');
    expect(screen.queryByRole('heading', { name: 'Net sales excluding tax' })).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /New sale/ })).not.toBeInTheDocument();
  });
  it('clears metrics immediately on refresh and applies revoked grants', async () => {
    grants(['inventory.view'], ['inventory', 'alerts']); open(); await ready('Inventory workspace');
    grants(['products.view'], []); await userEvent.click(screen.getByRole('button', { name: 'Refresh dashboard' })); await ready('Workspace overview');
    expect(screen.queryByRole('heading', { name: 'Recorded SKUs' })).not.toBeInTheDocument();
    expect(screen.getByText('No dashboard metrics available')).toBeInTheDocument();
  });
  it('keeps approved branch scope in requests and drill-downs', async () => {
    grants(['inventory.view'], ['inventory', 'alerts']); open(); await ready('Inventory workspace');
    await userEvent.selectOptions(screen.getByRole('combobox', { name: 'Branch' }), '2'); await ready('Inventory workspace');
    expect(within(card('Recorded SKUs')).getByRole('link')).toHaveAttribute('href', '/inventory?branch_id=2&tab=balances');
    workspaceApi.get.mock.calls.slice(-2).forEach(([path]) => expect(path).toContain('branch_id=2'));
  });
  it.each(['tenant_id', 'branch_ids', 'access_fingerprint', 'sales_scope', 'currency', 'timezone'])('rejects mismatched %s metadata before rendering any figures', async field => {
    grants(['inventory.view'], ['inventory', 'alerts']);
    workspaceApi.get.mockImplementation(async path => { const result = fixture(path); if (result.meta) result.meta[field] = field === 'branch_ids' ? [99] : 'different'; return result; });
    open(); expect(await screen.findByRole('alert')).toHaveTextContent('dashboard access or reporting scope changed');
    expect(screen.queryByRole('heading', { name: 'Recorded SKUs' })).not.toBeInTheDocument();
  });
  it('retains honest unavailable values for unset thresholds and missing summary fields', async () => {
    grants(['inventory.view'], ['inventory', 'alerts']); workspaceApi.get.mockImplementation(async path => { const result = fixture(path); if (path.startsWith('dashboard/summary')) { result.data.inventory.low_stock_threshold = null; result.data.inventory.low_stock_locations = null; delete result.data.inventory.recorded_skus; } return result; });
    open(); await ready('Inventory workspace');
    expect(within(card('Recorded SKUs')).getByText('Unavailable')).toBeInTheDocument();
    expect(within(card('Low stock locations')).queryByRole('link')).not.toBeInTheDocument();
  });
  it('treats recognized profile codes as ordering, never as an authorization grant', () => {
    grants(['inventory.view'], ['inventory', 'alerts']); tenant.dashboard.profile = 'owner';
    expect(dashboardPlan(tenant).resources).toEqual(['summary', 'alerts']);
    expect(dashboardPlan(tenant).has('sales')).toBe(false);
  });
});
