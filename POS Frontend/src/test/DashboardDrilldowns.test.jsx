import { cleanup, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import { workspaceApi } from '../api/workspace';
import InventoryPage from '../features/operations/InventoryPage';
import PurchasingPage from '../features/operations/PurchasingPage';
import ExpensesPage from '../features/operations/ExpensesPage';
import ReportsPage from '../features/operations/ReportsPage';
import { fixtureTenant } from './fixtures/businessDashboard';

vi.mock('../auth/AuthProvider', () => ({ useAuth: () => ({ user: { permissions: ['inventory.view', 'purchases.view', 'expenses.view', 'reports.view'] } }) }));
vi.mock('../api/workspace', () => ({ workspaceApi: { get: vi.fn() } }));
const page = data => ({ data, current_page: 1, last_page: 1, total: data.length });
const open = (component, path) => render(<MemoryRouter initialEntries={[path]} future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>{component}</MemoryRouter>);
beforeEach(() => {
  vi.resetAllMocks(); workspaceApi.get.mockImplementation(async path => {
    if (path === 'me/context') return { data: { active_tenant: fixtureTenant() } };
    if (path === 'inventory/adjustments/12') return { data: { id: 12, status: 'draft', reason: 'Stock review', items: [] } };
    if (path === 'purchasing/orders/17') return { data: { id: 17, status: 'draft', items: [] } };
    if (path.startsWith('reports/summary?')) return { data: { period: { from: '2026-11-01', to: '2026-11-03', timezone: 'Asia/Karachi' }, totals: { net_sales_excluding_tax: '77.6500' }, methods: {}, current_balances: { due: '0', refund_due: '0' }, reconciled: true } };
    return page([]);
  });
});
afterEach(cleanup);

describe('D3 drill-down consumers', () => {
  it('loads the exact inventory alert filters and clears them through navigation', async () => {
    open(<InventoryPage/>, '/inventory?tab=balances&location_id=3&variant_id=4');
    await waitFor(() => expect(workspaceApi.get).toHaveBeenCalledWith('inventory/balances?location_id=3&variant_id=4&page=1'));
    const filters = screen.getByRole('complementary', { name: 'Applied list filters' });
    expect(filters).toHaveTextContent('Location: 3');
    expect(filters).toHaveTextContent('Variant: 4');
    await userEvent.click(screen.getByRole('link', { name: 'Clear filters' }));
    await waitFor(() => expect(workspaceApi.get).toHaveBeenCalledWith('inventory/balances?page=1'));
    expect(screen.queryByRole('complementary')).not.toBeInTheDocument();
  });

  it('opens a pending stock document and resets incompatible filters on tab changes', async () => {
    open(<InventoryPage/>, '/inventory?tab=adjustments&document_id=12');
    expect(await screen.findByRole('heading', { name: 'adjustments #12 · draft' })).toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', { name: 'movements' }));
    await waitFor(() => expect(workspaceApi.get).toHaveBeenCalledWith('inventory/movements?page=1'));
    expect(screen.queryByRole('heading', { name: /adjustments #12/ })).not.toBeInTheDocument();
  });

  it('consumes purchase order status and document links', async () => {
    const view = open(<PurchasingPage/>, '/purchasing?tab=orders&branch_id=2&status=open');
    await waitFor(() => expect(workspaceApi.get).toHaveBeenCalledWith('purchasing/orders?branch_id=2&status=open&page=1'));
    view.unmount(); open(<PurchasingPage/>, '/purchasing?tab=orders&document_id=17');
    expect(await screen.findByRole('heading', { name: 'orders #17 · draft' })).toBeInTheDocument();
  });

  it('filters the expense queue to the requested approval record', async () => {
    open(<ExpensesPage/>, '/expenses?document_id=6');
    await waitFor(() => expect(workspaceApi.get).toHaveBeenCalledWith('expenses?document_id=6&page=1'));
    expect(screen.getByRole('complementary', { name: 'Applied list filters' })).toHaveTextContent('Document: 6');
  });

  it('auto-runs a branch report with the dashboard period rather than ignoring the URL', async () => {
    open(<ReportsPage/>, '/reports?from=2026-11-01&to=2026-11-03&branch_id=2');
    expect(await screen.findByText('77.6500')).toBeInTheDocument();
    expect(workspaceApi.get).toHaveBeenCalledWith('reports/summary?from=2026-11-01&to=2026-11-03&branch_id=2', expect.any(Object));
    expect(screen.getByLabelText('From date')).toHaveValue('2026-11-01');
    expect(screen.getByRole('combobox', { name: 'Report branch' })).toHaveValue('2');
    await userEvent.selectOptions(screen.getByRole('combobox', { name: 'Report branch' }), '1');
    await userEvent.click(screen.getByRole('button', { name: 'Run report' }));
    await waitFor(() => expect(workspaceApi.get).toHaveBeenCalledWith('reports/summary?from=2026-11-01&to=2026-11-03&branch_id=1', expect.any(Object)));
  });
});
