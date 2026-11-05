import { cleanup, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, useLocation } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { workspaceApi } from '../api/workspace';
import CatalogPage from '../features/CatalogPage';
import { RemoteTable } from '../features/operations/OperationUI';
import DashboardDrilldown from '../features/operations/DashboardDrilldown';
import { ProductForm } from '../features/ProductForms';
import { FormDetails, Input, SaveForm } from '../components/WorkspaceForm';

vi.mock('../auth/AuthProvider', () => ({ useAuth: () => ({ user: { permissions: ['products.manage'], active_tenant: { name: 'Test shop', currency_code: 'PKR' } } }) }));
vi.mock('../api/workspace', () => ({ workspaceApi: { get: vi.fn(), save: vi.fn(), exportCatalog: vi.fn() } }));
const page = (data = [], current = 1, last = 1, total = data.length) => ({ data, current_page: current, last_page: last, total, per_page: 2, from: data.length ? (current - 1) * 2 + 1 : null, to: data.length ? (current - 1) * 2 + data.length : null });
const product = (id, name) => ({ id, name, product_type: 'simple', active: true, track_inventory: true });
const open = node => render(<MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>{node}</MemoryRouter>);
beforeEach(() => { vi.resetAllMocks(); workspaceApi.get.mockResolvedValue(page()); });
afterEach(cleanup);

describe('server-paginated listing refinements', () => {
  it('requests only the next server page and resets search and clear to page one', async () => {
    workspaceApi.get.mockImplementation(async path => path.includes('search=missing') ? page() : path.includes('page=2') ? page([product(3, 'Third')], 2, 2, 3) : page([product(1, 'First'), product(2, 'Second')], 1, 2, 3));
    open(<CatalogPage />); const user = userEvent.setup();
    expect(await screen.findByText('Showing 1–2 · 3 total')).toBeInTheDocument();
    expect(workspaceApi.get).toHaveBeenCalledTimes(1);
    await user.click(screen.getByRole('button', { name: 'Next' }));
    expect(await screen.findByText('Third')).toBeInTheDocument();
    expect(screen.queryByText('First')).not.toBeInTheDocument();
    expect(workspaceApi.get).toHaveBeenLastCalledWith('products?page=2&search=');
    await user.type(screen.getByLabelText('Search products'), 'missing');
    await user.click(screen.getByRole('button', { name: 'Search' }));
    expect(await screen.findByRole('heading', { name: 'No matching products' })).toBeInTheDocument();
    expect(screen.queryByText('Your catalog starts here')).not.toBeInTheDocument();
    expect(workspaceApi.get).toHaveBeenLastCalledWith('products?page=1&search=missing');
    expect(screen.getByRole('complementary', { name: 'Applied list filters' })).toHaveTextContent('Search: missing');
    await user.click(screen.getByRole('button', { name: 'Clear filters' }));
    expect(await screen.findByText('First')).toBeInTheDocument();
    expect(screen.getByLabelText('Search products')).toHaveValue('');
    expect(workspaceApi.get).toHaveBeenLastCalledWith('products?page=1&search=');
  });

  it('distinguishes an empty catalog from a failed request', async () => {
    open(<CatalogPage />);
    expect(await screen.findByRole('heading', { name: 'Your catalog starts here' })).toBeInTheDocument();
    expect(screen.queryByText('No matching products')).not.toBeInTheDocument();
    cleanup(); workspaceApi.get.mockRejectedValueOnce(Error('Connection unavailable'));
    open(<CatalogPage />);
    expect(await screen.findByRole('alert')).toHaveTextContent('Unable to connect. Please try again.');
    expect(screen.queryByText('Your catalog starts here')).not.toBeInTheDocument();
  });

  it('locks page controls during requests, keeps filters, and drops old rows when scope changes', async () => {
    let resolveOldPage;
    workspaceApi.get.mockImplementation(path => path.includes('page=2') ? new Promise(resolve => { resolveOldPage = resolve; }) : Promise.resolve(page([{ id: 1, name: path.includes('branch_id=2') ? 'Second branch' : 'First branch' }], 1, 2, 3)));
    const view = open(<RemoteTable path="inventory/balances?branch_id=1" columns={[["name", "Name"]]} />);
    expect(await screen.findByText('First branch')).toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', { name: 'Next' }));
    await waitFor(() => expect(workspaceApi.get).toHaveBeenCalledWith('inventory/balances?branch_id=1&page=2'));
    expect(screen.getByRole('button', { name: 'Next' })).toBeDisabled();
    expect(screen.getByRole('region', { name: 'Records' })).toHaveAttribute('inert');
    view.rerender(<MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}><RemoteTable path="inventory/balances?branch_id=2" columns={[["name", "Name"]]} /></MemoryRouter>);
    expect(await screen.findByText('Second branch')).toBeInTheDocument();
    expect(workspaceApi.get).toHaveBeenLastCalledWith('inventory/balances?branch_id=2&page=1');
    resolveOldPage(page([{ id: 2, name: 'Late old branch response' }], 2, 2, 3));
    await waitFor(() => expect(screen.queryByText('Late old branch response')).not.toBeInTheDocument());
    expect(screen.queryByText('First branch')).not.toBeInTheDocument();
  });

  it('retries a failed filtered page without changing query scope', async () => {
    workspaceApi.get.mockRejectedValueOnce(Error('Synthetic offline')).mockResolvedValue(page());
    open(<RemoteTable label="Purchase orders" path="purchasing/orders?status=open&branch_id=4" columns={[["id", "Order"]]} />);
    expect(await screen.findByRole('alert')).toHaveTextContent('Unable to connect. Please try again.');
    expect(screen.queryByText('No matching records')).not.toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', { name: 'Try again' }));
    expect(await screen.findByRole('heading', { name: 'No matching records' })).toBeInTheDocument();
    expect(workspaceApi.get.mock.calls.map(([path]) => path)).toEqual(['purchasing/orders?status=open&branch_id=4&page=1', 'purchasing/orders?status=open&branch_id=4&page=1']);
  });

  it('keeps exact identifiers, monetary strings and accessible row actions', async () => {
    const sku = 'SCHOOL-EXTRA-LONG-SKU-000000000000000000001';
    const amount = '12345678901234567890.1234';
    workspaceApi.get.mockResolvedValue(page([{ id: 7, sku, total: amount }]));
    const select = vi.fn();
    open(<RemoteTable label="Orders" path="purchasing/orders" columns={[["sku", "SKU"], ["total", "Total"]]} onSelect={select} />);
    expect(await screen.findByText(amount)).toHaveClass('numeric');
    expect(screen.getByText(sku)).toHaveTextContent(sku);
    await userEvent.click(screen.getByRole('button', { name: 'Open' }));
    expect(select).toHaveBeenCalledWith(expect.objectContaining({ id: 7, total: amount }));
  });

  it('clears drill-down filters while retaining an explicit personal view', async () => {
    function Location() { return <output>{useLocation().search}</output>; }
    open(<><DashboardDrilldown query="own=1&branch_id=4&from=2026-11-01" clearTo="/sales" /><Location /></>);
    const filters = screen.getByRole('complementary', { name: 'Applied list filters' });
    expect(within(filters).getByText('Branch: 4')).toBeInTheDocument();
    await userEvent.click(screen.getByRole('link', { name: 'Clear filters' }));
    expect(screen.getByRole('status')).toHaveTextContent('?own=1');
  });

  it('does not offer a reset that would remove the personal scope', () => {
    open(<DashboardDrilldown query="own=1" clearTo="/sales" />);
    expect(screen.getByText('Personal scope: Your records')).toBeInTheDocument();
    expect(screen.queryByRole('link', { name: 'Clear filters' })).not.toBeInTheDocument();
  });
});

describe('grouped forms and optional details', () => {
  it('opens a closed section and focuses the invalid field on client validation', async () => {
    const save = vi.fn();
    open(<SaveForm onSave={save}><FormDetails title="Extra details"><Input name="reference" label="Reference" /></FormDetails></SaveForm>);
    const field = screen.getByLabelText('Reference'), details = field.closest('details');
    expect(details.open).toBe(false);
    await userEvent.click(screen.getByRole('button', { name: 'Save changes' }));
    await waitFor(() => expect(details.open).toBe(true));
    expect(field).toHaveFocus(); expect(save).not.toHaveBeenCalled();
  });

  it('reveals all sections with server errors while preserving entered values', async () => {
    const save = vi.fn().mockRejectedValue({ response: { status: 422, data: { errors: { reference: ['Reference already exists.'], description: ['Description rejected.'] } } } });
    open(<SaveForm onSave={save}><FormDetails title="References"><Input name="reference" label="Reference" value="KEEP-001" /></FormDetails><FormDetails title="Description details"><Input name="description" label="Description" value="Keep these details" /></FormDetails></SaveForm>);
    await userEvent.click(screen.getByRole('button', { name: 'Save changes' }));
    expect(await screen.findByText('Reference already exists.')).toBeInTheDocument();
    for (const name of ['Reference', 'Description']) expect(screen.getByLabelText(name).closest('details').open).toBe(true);
    expect(screen.getByLabelText('Reference')).toHaveFocus();
    expect(screen.getByLabelText('Description')).toHaveValue('Keep these details');
  });

  it('keeps closed optional product data and checkbox values when editing', async () => {
    workspaceApi.save.mockResolvedValue({ data: { id: 7 } });
    const saved = vi.fn();
    open(<ProductForm product={{ id: 7, name: 'Existing item', product_type: 'simple', description: 'Keep description', brand_id: 8, active: false, track_inventory: false }} units={[]} categories={[]} brands={[{ id: 8, name: 'Existing brand' }]} tax={[]} onSaved={saved} />);
    expect(screen.getByLabelText('Description').closest('details').open).toBe(false);
    fireEvent.change(screen.getByLabelText('Product name'), { target: { value: 'Renamed item' } });
    await userEvent.click(screen.getByRole('button', { name: 'Save product' }));
    await waitFor(() => expect(workspaceApi.save).toHaveBeenCalledWith('products/7', { name: 'Renamed item', product_type: 'simple', description: 'Keep description', brand_id: '8', category_id: null, tax_class_id: null, active: false, track_inventory: false }, true));
    expect(saved).toHaveBeenCalledOnce();
  });

  it('creates a first variant with exact prices and closed default settings', async () => {
    workspaceApi.save.mockResolvedValue({ data: { id: 9 } });
    open(<ProductForm units={[{ id: 4, name: 'Each' }]} categories={[]} brands={[]} tax={[]} onSaved={vi.fn()} />);
    const user = userEvent.setup();
    await user.type(screen.getByLabelText('Product name'), 'New item');
    await user.type(screen.getByLabelText('SKU'), 'UNIQUE-001');
    await user.selectOptions(screen.getByLabelText('Unit of measure'), '4');
    await user.clear(screen.getByLabelText('Default price')); await user.type(screen.getByLabelText('Default price'), '12.3456');
    await user.click(screen.getByRole('button', { name: 'Create product' }));
    await waitFor(() => expect(workspaceApi.save).toHaveBeenCalledWith('products', expect.objectContaining({ active: true, track_inventory: true, variant: expect.objectContaining({ sku: 'UNIQUE-001', default_price: '12.3456', base_unit_id: '4', tracking_mode: 'none', active: true }) }), false));
  });
});
