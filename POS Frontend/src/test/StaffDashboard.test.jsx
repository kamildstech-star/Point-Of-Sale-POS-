import { cleanup, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import WorkspaceOverview from '../features/dashboard/WorkspaceOverview';
import CashPage from '../features/operations/CashPage';
import SalesPage from '../features/operations/SalesPage';
import { workspaceApi } from '../api/workspace';
import { dashboardFixture, fixtureTenant } from './fixtures/businessDashboard';

let tenant, staff;
vi.mock('../auth/AuthProvider', () => ({ useAuth: () => ({ user: staff }) }));
vi.mock('../api/workspace', () => ({ workspaceApi: { get: vi.fn(), save: vi.fn() } }));
const receipt = { id: 31, invoice_no: 'INV-OWN-31', sold_at: '2026-11-03T10:00:00Z', branch_id: 1, cash_session_id: 5, grand_total: '24.69', currency_code: 'PKR', payment_status: 'paid', sale_status: 'completed' };
const pagination = { current_page: 1, last_page: 1, per_page: 5, total: 1 };
function fixture(path) {
  if (path === 'me/context') return { data: { active_tenant: tenant } };
  const url = new URL(path, 'http://fixture.test'); const resource = url.pathname.split('/').at(-1);
  const base = dashboardFixture(path.replace(/(cashier-summary|staff-activity|recent-sales)(\?|$)/, 'summary$2'), tenant);
  if (resource === 'cashier-summary') { base.meta.sales_scope = 'own'; base.data = { scope: 'own', open_sessions: 1, sessions: [{ id: 5, register_id: 2, register_name: 'Front counter', branch_id: 1, branch_name: 'Main Store', opened_at: '2026-11-03T08:00:00Z' }], selected_session: { id: 5, register_name: 'Front counter', branch_id: 1, branch_name: 'Main Store', opened_at: '2026-11-03T08:00:00Z' }, shift_totals: { invoice_count: 1, net_invoiced_including_tax: '12.3400' }, receipts: { items: [receipt], pagination }, held_carts: { count: 2 } }; }
  if (resource === 'recent-sales') base.data = { items: [receipt], pagination };
  if (resource === 'staff-activity') base.data = { items: [{ membership_id: 7, name: 'Branch cashier', invoice_count: 1, net_sales_excluding_tax: '77.65', open_sessions: 1 }], pagination };
  if (resource === 'summary') base.data.sales.average_invoice_value_including_tax = '25.88';
  return base;
}
const open = (element = <WorkspaceOverview/>, path = '/') => render(<MemoryRouter initialEntries={[path]} future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>{element}</MemoryRouter>);
const ready = name => screen.findByRole('heading', { name }, { timeout: 10000 });
beforeEach(() => {
  vi.resetAllMocks(); tenant = fixtureTenant(); tenant.dashboard = { ...tenant.dashboard, profile: 'cashier', held_sales: true, allowed_widgets: ['cashier'] };
  staff = { id: 4, name: 'Staff Fixture', active_tenant: { id: 1, timezone: tenant.timezone, currency_code: tenant.currency }, permissions: [...tenant.permissions, 'sales.return'] };
  tenant.permissions = staff.permissions; workspaceApi.get.mockImplementation(async path => fixture(path));
});
afterEach(cleanup);

describe('D4 staff dashboards', () => {
  it('offers a prominent New Sale and personal hold, receipt and shift destinations', async () => {
    open(); await ready('Cashier Dashboard');
    expect(screen.getByRole('link', { name: 'New Sale' })).toHaveAttribute('href', '/pos?own=1&cash_session_id=5');
    expect(screen.getByRole('link', { name: 'Hold / Recall (2)' })).toHaveAttribute('href', '/pos?held=1&own=1&cash_session_id=5');
    expect(screen.getByRole('link', { name: 'INV-OWN-31' })).toHaveAttribute('href', '/sales/31?own=1');
    expect(screen.getByRole('link', { name: 'Return' })).toHaveAttribute('href', '/returns/new/31');
    expect(screen.getByRole('link', { name: /Review or close this shift/ })).toHaveAttribute('href', '/cash?own=1&document_id=5&branch_id=1');
    expect(screen.queryByRole('heading', { name: /profit/i })).not.toBeInTheDocument();
    expect(workspaceApi.get.mock.calls.filter(([path]) => path.startsWith('dashboard/'))).toHaveLength(1);
  });

  it('requires opening a shift and keeps missing shift totals unavailable', async () => {
    workspaceApi.get.mockImplementation(async path => { const value = fixture(path); if (path.startsWith('dashboard/cashier-summary')) Object.assign(value.data, { open_sessions: 0, sessions: [], selected_session: null, shift_totals: null }); return value; });
    open(); await ready('Cashier Dashboard');
    expect(screen.getByRole('link', { name: 'Open register shift' })).toHaveAttribute('href', '/cash?own=1&open=1');
    expect(screen.queryByRole('link', { name: 'New Sale' })).not.toBeInTheDocument();
    expect(within(screen.getByRole('heading', { name: 'Your shift sales' }).closest('article')).getByText('Unavailable')).toBeInTheDocument();
  });

  it('requests only personal data and today in the tenant timezone, even with reporting permission', async () => {
    open(); await ready('Cashier Dashboard');
    const paths = workspaceApi.get.mock.calls.map(([path]) => path);
    expect(paths.some(path => path.startsWith('dashboard/summary'))).toBe(false);
    const query = new URL(paths.find(path => path.startsWith('dashboard/')), 'http://fixture.test').searchParams;
    expect(query.get('from')).toBe(query.get('to'));
    expect(screen.getByText(/Your activity only/)).toBeInTheDocument();
  });

  it('refetches personal shift selection and resets receipt pagination with branch changes', async () => {
    open(); await ready('Cashier Dashboard');
    await userEvent.selectOptions(screen.getByRole('combobox', { name: 'Your open shift' }), '5'); await ready('Cashier Dashboard');
    expect(workspaceApi.get.mock.calls.some(([path]) => path.includes('cash_session_id=5'))).toBe(true);
    await userEvent.selectOptions(screen.getByRole('combobox', { name: 'Branch' }), '2'); await ready('Cashier Dashboard');
    const path = workspaceApi.get.mock.calls.at(-1)[0]; expect(path).toContain('branch_id=2'); expect(path).not.toContain('cash_session_id=');
  });

  it('uses one permitted branch for manager metrics, staff activity and genuine drill-downs', async () => {
    tenant.dashboard.profile = 'manager'; tenant.dashboard.allowed_widgets = ['sales', 'sales-trend', 'recent-sales', 'staff-activity', 'inventory', 'alerts', 'approvals'];
    open(); await ready('Branch Manager Dashboard');
    const paths = workspaceApi.get.mock.calls.map(([path]) => path).filter(path => path.startsWith('dashboard/'));
    expect(paths).toHaveLength(5); paths.forEach(path => expect(path).toContain('branch_id=1'));
    expect(screen.getByRole('combobox', { name: 'Branch' })).toHaveValue('1');
    expect(screen.queryByRole('option', { name: 'All permitted branches' })).not.toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Branch cashier' })).toHaveAttribute('href', expect.stringContaining('cashier_membership_id=7'));
    expect(screen.getByRole('link', { name: 'Review approvals' })).toHaveAttribute('href', '#pending-branch-approvals');
    expect(screen.getByRole('heading', { name: 'Average invoice value' })).toBeInTheDocument();
  });

  it('does not request any branch totals when a manager has no permitted branches', async () => {
    tenant.dashboard.profile = 'manager'; tenant.allowed_branches = []; tenant.branch_scope = 'assigned';
    open(); await ready('Branch Manager Dashboard');
    expect(screen.getByText('No permitted branches')).toBeInTheDocument(); expect(workspaceApi.get).toHaveBeenCalledTimes(1);
  });

  it('places manager approvals before supporting staff activity without changing branch context', async () => {
    tenant.dashboard.profile = 'manager'; tenant.dashboard.allowed_widgets = ['sales', 'sales-trend', 'staff-activity', 'inventory', 'alerts', 'approvals'];
    open(); await ready('Branch Manager Dashboard');
    const approvals = screen.getByRole('heading', { name: 'Pending branch approvals' });
    expect(approvals.compareDocumentPosition(screen.getByRole('heading', { name: 'Branch staff activity' })) & Node.DOCUMENT_POSITION_FOLLOWING).toBeTruthy();
    expect(screen.getByLabelText('Dashboard context')).toHaveTextContent('Main Store');
    workspaceApi.get.mock.calls.filter(([path]) => path.startsWith('dashboard/')).forEach(([path]) => expect(path).toContain('branch_id=1'));
  });

  it('omits staff activity under personal-sales restrictions and applies real quick periods', async () => {
    tenant.dashboard.profile = 'manager'; tenant.dashboard.sales_scope = 'own'; tenant.sales_scope = 'own'; tenant.dashboard.allowed_widgets = ['sales', 'sales-trend'];
    open(); await ready('Branch Manager Dashboard');
    expect(screen.queryByRole('heading', { name: 'Branch staff activity' })).not.toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', { name: 'Today' })); await ready('Branch Manager Dashboard');
    const query = new URL(workspaceApi.get.mock.calls.at(-1)[0], 'http://fixture.test').searchParams; expect(query.get('from')).toBe(query.get('to'));
    expect(screen.queryByRole('link', { name: /View branch report/ })).not.toBeInTheDocument();
  });

  it('fails closed when the personal endpoint refuses access', async () => {
    workspaceApi.get.mockImplementation(async path => { if (path.startsWith('dashboard/')) throw { response: { status: 403, data: { message: 'Access changed.' } } }; return fixture(path); });
    open(); expect(await screen.findByRole('alert')).toHaveTextContent('Access changed'); expect(screen.queryByRole('link', { name: 'New Sale' })).not.toBeInTheDocument();
  });

  it('consumes personal and branch sales filters in the existing sales page', async () => {
    workspaceApi.get.mockResolvedValue({ data: [], last_page: 1 });
    open(<SalesPage/>, '/sales?from=2026-11-01&to=2026-11-03&branch_id=1&cashier_membership_id=7&own=1&cash_session_id=5');
    await waitFor(() => expect(workspaceApi.get).toHaveBeenCalledWith('sales?due=0&from=2026-11-01&to=2026-11-03&branch_id=1&cashier_membership_id=7&own=1&cash_session_id=5&page=1'));
  });

  it('opens the actual shift form from the cashier start action and filters registers', async () => {
    workspaceApi.get.mockResolvedValue({ data: [], last_page: 1 }); open(<CashPage/>, '/cash?own=1&branch_id=1&open=1');
    expect(await screen.findByRole('dialog', { name: 'Open shift' })).toBeInTheDocument();
    await waitFor(() => expect(workspaceApi.get).toHaveBeenCalledWith('operations/registers?branch_id=1'));
    expect(workspaceApi.get).toHaveBeenCalledWith('cash/sessions?branch_id=1&own=1&page=1');
  });
});
