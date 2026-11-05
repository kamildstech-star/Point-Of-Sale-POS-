import { act, cleanup, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import WorkspaceOverview from '../features/dashboard/WorkspaceOverview';
import { workspaceApi } from '../api/workspace';
import { defaultPeriod } from '../features/dashboard/dashboardData';
import { dashboardFixture, fixtureTenant } from './fixtures/businessDashboard';

let tenant, staff;
vi.mock('../auth/AuthProvider', () => ({ useAuth: () => ({ user: staff }) }));
vi.mock('../api/workspace', () => ({ workspaceApi: { get: vi.fn() } }));
const open = () => render(<MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}><WorkspaceOverview/></MemoryRouter>);
const ready = () => screen.findByRole('heading', { name: 'Business Owner Dashboard' }, { timeout: 10000 });
const card = title => screen.getByRole('heading', { name: title }).closest('article');
beforeEach(() => {
  vi.resetAllMocks(); tenant = fixtureTenant();
  staff = { id: 4, name: 'Owner Example', role: { name: 'Owner' }, active_tenant: { id: tenant.id }, permissions: tenant.permissions };
  workspaceApi.get.mockImplementation(async path => dashboardFixture(path, tenant));
});
afterEach(cleanup);

describe('D3 business dashboards', () => {
  it('prioritizes verified owner balances and exceptions before secondary tools', async () => {
    open(); await ready();
    const primary = screen.getByRole('region', { name: 'Key performance indicators' });
    expect(within(primary).getByRole('heading', { name: 'Current receivables' })).toBeInTheDocument();
    expect(within(primary).queryByRole('heading', { name: 'Gross profit' })).not.toBeInTheDocument();
    expect(within(card('Gross profit')).getByText('Unavailable')).toBeInTheDocument();
    const attention = screen.getByRole('region', { name: 'Action required' });
    expect(within(attention).getByRole('heading', { name: 'Stock alerts' })).toBeInTheDocument();
    expect(attention.compareDocumentPosition(screen.getByRole('heading', { name: 'Quick actions' })) & Node.DOCUMENT_POSITION_FOLLOWING).toBeTruthy();
    const context = screen.getByLabelText('Dashboard context');
    expect(context).toHaveTextContent('All permitted branches'); expect(context).toHaveTextContent('PKR');
    expect(workspaceApi.get.mock.calls.map(([path]) => path.split('?')[0]).filter(path => path.startsWith('dashboard/')).sort()).toEqual(['dashboard/alerts', 'dashboard/branch-performance', 'dashboard/sales-trend', 'dashboard/summary', 'dashboard/top-products']);
  });

  it('uses server decimal amounts and meaningful scoped links; missing profit stays unavailable', async () => {
    open(); await ready();
    expect(within(card('Net sales excluding tax')).getByText(/77\.65/)).toBeInTheDocument();
    expect(within(card('Gross profit')).getByText('Unavailable')).toBeInTheDocument();
    expect(within(card('Low stock locations')).getByRole('link')).toHaveAttribute('href', '/inventory?tab=balances&stock_state=low');
    expect(screen.getByRole('link', { name: 'Review alert' })).toHaveAttribute('href', '/inventory?tab=balances&location_id=3&variant_id=4');
    expect(screen.getByRole('link', { name: 'Cotton shirt' })).toHaveAttribute('href', '/catalog/8');
    expect(screen.getByRole('link', { name: 'Main Store' })).toHaveAttribute('href', expect.stringContaining('branch_id=1'));
    expect(screen.getByRole('link', { name: 'Review stock adjustments 12' })).toHaveAttribute('href', '/inventory?tab=adjustments&document_id=12');
    expect(screen.getByRole('link', { name: 'Review purchase orders 17' })).toHaveAttribute('href', '/purchasing?tab=orders&document_id=17');
    expect(screen.getByRole('link', { name: 'Review expenses 6' })).toHaveAttribute('href', '/expenses?document_id=6');
    expect(screen.getByText(/Percentage unavailable because/)).toBeInTheDocument();
  });

  it('presents an admin operational variant without changing grants', async () => {
    tenant.dashboard.profile = 'admin'; open();
    await screen.findByRole('heading', { name: 'Business Admin Dashboard' }, { timeout: 10000 });
    expect(within(card('Open shifts')).getByText('2')).toBeInTheDocument();
    expect(within(card('Open purchase orders')).getByRole('link')).toHaveAttribute('href', '/purchasing?tab=orders&status=open');
    expect(screen.getByRole('link', { name: /User access/ })).toHaveAttribute('href', '/team');
    expect(screen.queryByRole('heading', { name: 'Gross profit' })).not.toBeInTheDocument();
  });

  it('uses verified capabilities even when the client role claims Owner', async () => {
    tenant.dashboard.allowed_widgets = ['inventory', 'alerts']; tenant.permissions = ['dashboard.view', 'inventory.view'];
    open(); await ready();
    expect(screen.queryByRole('heading', { name: 'Net sales excluding tax' })).not.toBeInTheDocument();
    expect(screen.queryByRole('heading', { name: 'Sales trend' })).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /New sale/ })).not.toBeInTheDocument();
    expect(workspaceApi.get.mock.calls.map(([path]) => path).filter(path => path.startsWith('dashboard/'))).toHaveLength(2);
  });

  it('composes unprofiled dashboards from verified capabilities', async () => {
    tenant.dashboard.profile = null; open(); await screen.findByRole('heading', { name: 'Accounting overview' }, { timeout: 10000 });
    expect(screen.getByRole('heading', { name: 'Current receivables' })).toBeInTheDocument();
    expect(workspaceApi.get.mock.calls.some(([path]) => path.startsWith('dashboard/branch-performance'))).toBe(false);
  });

  it('refetches every dependent widget on branch changes and passes the selected scope to drill-downs', async () => {
    open(); await ready(); const before = workspaceApi.get.mock.calls.length;
    await userEvent.selectOptions(screen.getByRole('combobox', { name: 'Branch' }), '2'); await ready();
    expect(within(card('Net sales excluding tax')).getByText(/12\.35/)).toBeInTheDocument();
    const requests = workspaceApi.get.mock.calls.slice(before).map(([path]) => path).filter(path => path.startsWith('dashboard/'));
    expect(requests).toHaveLength(5); requests.forEach(path => expect(new URL(path, 'http://fixture.test').searchParams.get('branch_id')).toBe('2'));
    expect(within(card('Low stock locations')).getByRole('link')).toHaveAttribute('href', '/inventory?branch_id=2&tab=balances&stock_state=low');
  });

  it('validates dates, then sends actual dates to all widgets and report links', async () => {
    open(); await ready();
    fireEvent.change(screen.getByLabelText('From date'), { target: { value: '2026-11-04' } });
    fireEvent.change(screen.getByLabelText('Through date'), { target: { value: '2026-11-03' } });
    const before = workspaceApi.get.mock.calls.length;
    await userEvent.click(screen.getByRole('button', { name: 'Apply period' })); expect(workspaceApi.get.mock.calls).toHaveLength(before);
    fireEvent.change(screen.getByLabelText('From date'), { target: { value: '2026-11-01' } });
    await userEvent.click(screen.getByRole('button', { name: 'Apply period' })); await ready();
    expect(within(card('Net sales excluding tax')).getByRole('link')).toHaveAttribute('href', '/reports?from=2026-11-01&to=2026-11-03');
  });

  it('handles a single widget failure with an inline retry and retains verified widgets', async () => {
    workspaceApi.get.mockImplementation(async path => { if (path.startsWith('dashboard/sales-trend')) throw new Error('Trend offline'); return dashboardFixture(path, tenant); });
    open(); await ready(); expect(within(card('Net sales excluding tax')).getByText(/77\.65/)).toBeInTheDocument();
    const chart = screen.getByRole('heading', { name: 'Sales trend' }).closest('section');
    expect(within(chart).getByRole('alert')).toBeInTheDocument();
    workspaceApi.get.mockImplementation(async path => dashboardFixture(path, tenant));
    await userEvent.click(within(chart).getByRole('button', { name: 'Try again' })); await ready();
    expect(screen.getByRole('img', { name: /Daily net sales/ })).toBeInTheDocument();
  });

  it('fails closed on revoked widget access and never displays a partial previous policy', async () => {
    workspaceApi.get.mockImplementation(async path => { if (path.startsWith('dashboard/top-products')) throw { response: { status: 403 } }; return dashboardFixture(path, tenant); });
    open(); await screen.findByRole('alert');
    expect(screen.queryByRole('heading', { name: 'Net sales excluding tax' })).not.toBeInTheDocument();
  });

  it('refuses cross-business and stale-policy response metadata', async () => {
    workspaceApi.get.mockImplementation(async path => { const result = dashboardFixture(path, tenant); if (result.meta) result.meta.tenant_id = 99; return result; });
    open(); await screen.findByRole('alert');
    expect(screen.getByRole('alert')).toHaveTextContent('dashboard access or reporting scope changed');
    expect(screen.queryByText(/77\.65/)).not.toBeInTheDocument();
  });

  it('retries a revision mismatch once, then gives an explicit error instead of mixed figures', async () => {
    workspaceApi.get.mockImplementation(async path => dashboardFixture(path, tenant, { revision: path.startsWith('dashboard/summary') ? 1 : 2 }));
    open(); await screen.findByRole('alert');
    expect(screen.getByRole('alert')).toHaveTextContent('load a consistent view');
    expect(workspaceApi.get.mock.calls.filter(([path]) => path.startsWith('dashboard/summary'))).toHaveLength(2);
    expect(screen.queryByRole('heading', { name: 'Net sales excluding tax' })).not.toBeInTheDocument();
  });

  it('ignores late responses for the preceding filter', async () => {
    open(); await ready(); let release;
    const pending = new Promise(resolve => { release = resolve; });
    workspaceApi.get.mockImplementation(async path => { if (path.includes('branch_id=1')) await pending; return dashboardFixture(path, tenant); });
    await userEvent.selectOptions(screen.getByRole('combobox', { name: 'Branch' }), '1');
    expect(screen.queryByRole('heading', { name: 'Net sales excluding tax' })).not.toBeInTheDocument();
    // Unmounting is equivalent to a tenant/navigation change while requests run.
    cleanup(); tenant = fixtureTenant({ id: 2 }); staff = { ...staff, active_tenant: { id: 2 } }; open(); await ready();
    await act(async () => release());
    expect(within(card('Net sales excluding tax')).getByText(/77\.65/)).toBeInTheDocument();
  });

  it('resets a revoked branch filter and does not invent branch access with no assignments', async () => {
    open(); await ready();
    await userEvent.selectOptions(screen.getByRole('combobox', { name: 'Branch' }), '2'); await ready();
    tenant.allowed_branches = []; tenant.branch_scope = 'assigned';
    await userEvent.click(screen.getByRole('button', { name: 'Refresh dashboard' })); await ready();
    expect(screen.getByRole('combobox', { name: 'Branch' })).toHaveValue('');
    expect(screen.getByText('No permitted branches')).toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /Add product/ })).not.toBeInTheDocument();
  });

  it('uses the business timezone when its calendar date differs from UTC', () => {
    expect(defaultPeriod('Asia/Karachi', new Date('2026-11-02T22:30:00Z'))).toEqual({ from: '2026-10-05', to: '2026-11-03' });
  });

  it('does not reload unchanged reporting dates but keeps deliberate refresh authoritative', async () => {
    open(); await ready(); const before = workspaceApi.get.mock.calls.length;
    await userEvent.click(screen.getByRole('button', { name: 'Apply period' }));
    expect(workspaceApi.get.mock.calls).toHaveLength(before);
    await userEvent.click(screen.getByRole('button', { name: 'Refresh dashboard' })); await ready();
    expect(workspaceApi.get.mock.calls).toHaveLength(before + 6);
    expect(workspaceApi.get.mock.calls.slice(before)[0][0]).toBe('me/context');
  });

  it('does not dispatch widget requests after a canceled context load resolves late', async () => {
    let finish;
    workspaceApi.get.mockImplementation(path => path === 'me/context' ? new Promise(resolve => { finish = resolve; }) : Promise.resolve(dashboardFixture(path, tenant)));
    const view = open(); view.unmount();
    await act(async () => finish({ data: { active_tenant: tenant } }));
    expect(workspaceApi.get.mock.calls.map(([path]) => path)).toEqual(['me/context']);
  });
});
