import { cleanup, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import WorkspaceOverview from '../features/dashboard/WorkspaceOverview';
import PlatformWorkspace from '../features/dashboard/PlatformWorkspace';
import InventoryPage from '../features/operations/InventoryPage';
import PurchasingPage from '../features/operations/PurchasingPage';
import { ThemeProvider } from '../theme/ThemeProvider';
import { workspaceApi } from '../api/workspace';
import { specialistTenant, specialistFixture, platformFixture } from './fixtures/specialistDashboard';

let tenant, staff;
vi.mock('../auth/AuthProvider', () => ({ useAuth: () => ({ user: staff, logout: vi.fn() }) }));
vi.mock('../api/workspace', () => ({ workspaceApi: { get: vi.fn(), save: vi.fn() } }));
const open = (element = <WorkspaceOverview/>, path = '/') => render(<ThemeProvider userId={83}><MemoryRouter initialEntries={[path]} future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>{element}</MemoryRouter></ThemeProvider>);
const ready = name => screen.findByRole('heading', { name }, { timeout: 10000 });
const endpoints = () => workspaceApi.get.mock.calls.map(([path]) => path).filter(path => path.startsWith('dashboard/'));
function configure(kind) { tenant = specialistTenant(kind); staff = { id: 83, name: 'Synthetic Staff', permissions: tenant.permissions, active_tenant: { id: tenant.id, timezone: tenant.timezone } }; }
beforeEach(() => { vi.resetAllMocks(); configure('inventory'); workspaceApi.get.mockImplementation(async path => specialistFixture(path, tenant)); });
afterEach(cleanup);

describe('D5 specialist activity', () => {
  it('keeps movement units separate, fetches the next unit page, and resets all pages with branch changes', async () => {
    open(); await ready('Stock movement');
    expect(screen.getByRole('link', { name: 'Stock adjustment' })).toHaveAttribute('href', '/inventory?tab=adjustments&create=1');
    expect(screen.getByRole('combobox', { name: 'Movement unit' })).toHaveValue('1');
    await userEvent.click(within(screen.getByRole('navigation', { name: 'Movement unit results' })).getByRole('button', { name: 'Next' }));
    await waitFor(() => expect(screen.getByRole('combobox', { name: 'Movement unit' })).toHaveValue('2'));
    expect(endpoints().at(-1)).toContain('page=2');
    await userEvent.selectOptions(screen.getByRole('combobox', { name: 'Branch' }), '2');
    await waitFor(() => expect(screen.getByRole('combobox', { name: 'Movement unit' })).toHaveValue('1'));
    expect(endpoints().at(-1)).toContain('branch_id=2'); expect(endpoints().at(-1)).toContain('page=1');
    expect(screen.getByRole('link', { name: 'Stock adjustment' })).toHaveAttribute('href', '/inventory?tab=adjustments&branch_id=2&create=1');
    expect(screen.getByText(/Stock transfers are unavailable/)).toBeInTheDocument();
    expect(new Set(endpoints().map(path => path.split('?')[0]))).toEqual(new Set(['dashboard/summary', 'dashboard/alerts', 'dashboard/inventory-trend']));
  });
  it('shows current open orders and supplier activity with independent server pagination', async () => {
    configure('purchasing'); open(); await ready('Purchase order status and open orders');
    expect(screen.getByRole('link', { name: 'Create purchase order' })).toHaveAttribute('href', '/purchasing?tab=orders&create=1');
    await userEvent.selectOptions(screen.getByRole('combobox', { name: 'Branch' }), '1');
    await waitFor(() => expect(screen.getByRole('link', { name: 'SYNTH-PO-1' })).toHaveAttribute('href', '/purchasing?tab=orders&branch_id=1&document_id=17'));
    await userEvent.click(within(screen.getByRole('navigation', { name: 'Open order results' })).getByRole('button', { name: 'Next' }));
    expect(await screen.findByRole('link', { name: 'SYNTH-PO-2' })).toBeInTheDocument();
    expect(screen.getByText('Synthetic supplier 1')).toBeInTheDocument();
    expect(screen.getByText(/delivery dates.*unavailable/)).toBeInTheDocument();
    expect(new Set(endpoints().map(path => path.split('?')[0]))).toEqual(new Set(['dashboard/summary', 'dashboard/purchase-orders', 'dashboard/supplier-overview']));
  });
  it('uses recorded payment flows and real cash exceptions without inventing profit or bank balances', async () => {
    configure('accounting'); open(); await ready('Revenue and expenses');
    expect(screen.getByRole('table', { name: 'Recorded payment flows, including older invoices' })).toHaveTextContent('bank');
    expect(screen.getByText(/Bank balances, verified profit.*unavailable/)).toBeInTheDocument();
    await userEvent.selectOptions(screen.getByRole('combobox', { name: 'Branch' }), '2');
    await waitFor(() => expect(screen.getByRole('link', { name: 'Shift #5' })).toHaveAttribute('href', '/cash?branch_id=2&document_id=5'));
  });
  it('keeps a failed secondary widget local and reloads verified context on retry', async () => {
    let failed = true;
    workspaceApi.get.mockImplementation(async path => { if (failed && path.startsWith('dashboard/inventory-trend')) throw { response: { status: 503, data: { message: 'Movement temporarily unavailable.' } } }; return specialistFixture(path, tenant); });
    open(); await ready('Inventory workspace');
    expect(await screen.findByText('Movement temporarily unavailable.')).toBeInTheDocument();
    failed = false; await userEvent.click(screen.getByRole('button', { name: 'Try again' }));
    expect(await screen.findByRole('combobox', { name: 'Movement unit' })).toBeInTheDocument();
    expect(workspaceApi.get.mock.calls.filter(([path]) => path === 'me/context')).toHaveLength(2);
  });
  it('rolls back to the existing specialist summary when pilot capabilities are removed', async () => {
    tenant.dashboard.allowed_widgets = ['inventory', 'alerts']; tenant.dashboard.specialist_enabled = false;
    open(); await ready('Inventory workspace');
    expect(screen.queryByRole('heading', { name: 'Stock movement' })).not.toBeInTheDocument();
    expect(endpoints().some(path => path.includes('inventory-trend'))).toBe(false);
  });
  it.each([['inventory', <InventoryPage/>, '/inventory?tab=adjustments&branch_id=2&create=1', 'New adjustments'], ['purchasing', <PurchasingPage/>, '/purchasing?tab=orders&branch_id=2&create=1', 'Draft purchase order']])('opens the existing %s form without posting a record', async (kind, element, path, title) => {
    configure(kind); workspaceApi.get.mockImplementation(async path => path === 'me/context' ? { data: { active_tenant: tenant } } : { data: [], current_page: 1, last_page: 1, total: 0 });
    open(element, path); expect(await screen.findByRole('dialog', { name: title })).toBeInTheDocument();
    expect(workspaceApi.save).not.toHaveBeenCalled();
  });
  it('refuses an adjustment create URL without its existing mutation grant', async () => {
    staff.permissions = ['inventory.view']; workspaceApi.get.mockResolvedValue({ data: [], current_page: 1, last_page: 1 });
    open(<InventoryPage/>, '/inventory?tab=adjustments&create=1');
    await waitFor(() => expect(workspaceApi.get).toHaveBeenCalled());
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument(); expect(workspaceApi.save).not.toHaveBeenCalled();
  });
});

describe('D6 personal sales and platform separation', () => {
  it('requests personal sales only, even when the associate also has reporting permissions', async () => {
    configure('associate'); open(); await ready('Sales Associate Dashboard');
    expect(endpoints()).toHaveLength(1); expect(endpoints()[0]).toContain('dashboard/personal-sales?');
    expect(screen.getByRole('link', { name: 'SYNTH-OWN-31' })).toHaveAttribute('href', '/sales/31?own=1');
    expect(screen.queryByRole('heading', { name: 'Revenue and expenses' })).not.toBeInTheDocument();
    expect(within(screen.getByRole('heading', { name: 'Assigned customers' }).closest('article')).getByText('Unavailable')).toBeInTheDocument();
  });
  it('rejects an unscoped response from the personal endpoint', async () => {
    configure('associate'); workspaceApi.get.mockImplementation(async path => { const result = specialistFixture(path, tenant); if (result.meta) result.meta.sales_scope = 'permitted_branches'; return result; });
    open(); expect(await screen.findByRole('alert')).toHaveTextContent(/scope|context|changed/i);
    expect(screen.queryByRole('link', { name: 'SYNTH-OWN-31' })).not.toBeInTheDocument();
  });
  function platform(enabled = true) {
    staff = { id: 83, name: 'Synthetic Platform', permissions: [], platform_permissions: ['dashboard.platform.view'], active_tenant: null };
    workspaceApi.get.mockImplementation(async path => path === 'platform/context' ? { data: { scope: 'platform', user_id: 83, permissions: ['dashboard.platform.view'], dashboard_enabled: enabled } } : platformFixture(path));
    open(<PlatformWorkspace/>, '/platform/dashboard');
  }
  it('loads only platform context and aggregates, with no tenant switcher or tenant details', async () => {
    platform(); await ready('Platform dashboard');
    expect(screen.getByRole('heading', { name: 'Tenant creation trend' })).toBeInTheDocument();
    expect(screen.queryByRole('link', { name: 'Business workspace' })).not.toBeInTheDocument();
    expect(screen.queryByRole('combobox', { name: 'Branch' })).not.toBeInTheDocument();
    expect(workspaceApi.get.mock.calls.every(([path]) => path.startsWith('platform/'))).toBe(true);
    expect(within(screen.getByRole('heading', { name: 'Monthly recurring revenue' }).closest('article')).getByText('Unavailable')).toBeInTheDocument();
  });
  it('does not request aggregates while the separate platform pilot is disabled', async () => {
    platform(false); await ready('Platform pilot is not enabled');
    expect(workspaceApi.get).toHaveBeenCalledTimes(1);
  });
  it('fails closed on platform permission or response-identity changes', async () => {
    staff = { id: 83, name: 'Synthetic Platform', active_tenant: null };
    workspaceApi.get.mockImplementation(async path => path === 'platform/context' ? { data: { scope: 'platform', user_id: 83, permissions: ['dashboard.platform.view'], dashboard_enabled: true } } : platformFixture(path, 999));
    open(<PlatformWorkspace/>, '/platform/dashboard'); expect(await screen.findByRole('alert')).toHaveTextContent('Platform response scope changed');
    expect(screen.queryByRole('heading', { name: 'Non-archived tenants' })).not.toBeInTheDocument();
  });
  it('clears platform totals when a refresh finds a revoked grant', async () => {
    platform(); await ready('Non-archived tenants');
    workspaceApi.get.mockRejectedValue({ response: { status: 403, data: { message: 'Platform permission revoked.' } } });
    await userEvent.click(screen.getByRole('button', { name: 'Refresh dashboard' }));
    expect(await screen.findByRole('alert')).toHaveTextContent('Platform permission revoked.');
    expect(screen.queryByRole('heading', { name: 'Non-archived tenants' })).not.toBeInTheDocument();
  });
  it('discards a late platform response after leaving its scope', async () => {
    let release;
    staff = { id: 83, name: 'Synthetic Platform', active_tenant: null };
    workspaceApi.get.mockImplementation(path => path === 'platform/context' ? Promise.resolve({ data: { scope: 'platform', user_id: 83, permissions: ['dashboard.platform.view'], dashboard_enabled: true } }) : new Promise(resolve => { release = () => resolve(platformFixture(path)); }));
    const view = open(<PlatformWorkspace/>, '/platform/dashboard');
    await waitFor(() => expect(release).toBeTypeOf('function')); view.unmount(); release();
    expect(screen.queryByRole('heading', { name: 'Non-archived tenants' })).not.toBeInTheDocument();
  });
});
