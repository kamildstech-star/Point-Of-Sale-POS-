import { cleanup, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import PosPage from '../features/operations/PosPage';
import HeldCartsDialog from '../features/operations/HeldCartsDialog';
import { workspaceApi } from '../api/workspace';

vi.mock('../auth/AuthProvider', () => ({ useAuth: () => ({ user: { id: 4, permissions: ['sales.create', 'sales.view', 'sales.discount', 'cash.open', 'products.view'], active_tenant: { id: 1, currency_code: 'PKR', timezone: 'Asia/Karachi' } } }) }));
vi.mock('../api/workspace', () => ({ workspaceApi: { get: vi.fn(), save: vi.fn() } }));
const page = data => ({ data, total: data.length, current_page: 1, last_page: 1 });
const held = { id: 8, branch_id: 1, source_session_id: 5, label: 'Customer fitting', version: 4, status: 'held', item_count: 1, updated_at: '2026-11-03T10:00:00Z', cart: { customer_id: 2, price_list_id: 3, discount_amount: '0.0000', items: [{ variant_id: 9, name: 'Test shirt', sku: 'SHIRT-1', qty: '2.0000', active: true }] } };
const open = (path = '/pos', element = <PosPage/>) => render(<MemoryRouter initialEntries={[path]} future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>{element}</MemoryRouter>);
beforeEach(() => {
  vi.resetAllMocks();
  workspaceApi.get.mockImplementation(async path => {
    if (path === 'me/context') return { data: { active_tenant: { dashboard: { held_sales: true } } } };
    if (path.startsWith('cash/sessions')) return page([{ id: 5, register_id: 2, branch_id: 1 }]);
    if (path === 'operations/payment-methods') return page([{ id: 1, name: 'Cash', method_type: 'cash', active: true }]);
    if (path === 'operations/customers') return page([{ id: 2, name: 'Existing customer', active: true }]);
    if (path === 'catalog/price-lists') return page([{ id: 3, name: 'Retail', active: true }]);
    if (path.startsWith('pos/products')) return page([{ id: 9, name: 'Test shirt', sku: 'SHIRT-1', default_price: '12.3456' }]);
    if (path === 'pos/held-carts/8') return { data: held };
    if (path.startsWith('pos/held-carts')) return page([held]);
    return page([]);
  });
  workspaceApi.save.mockImplementation(async path => path === 'pos/quotes' ? { data: { id: 51, expires_at: new Date(Date.now() + 600000).toISOString(), quote: { subtotal: '24.69', discount_total: '0.00', tax_total: '0.00', grand_total: '24.69', currency_code: 'PKR', currency_scale: 2, allow_credit: true } } }
    : path === 'pos/checkout' ? { data: { id: 81, invoice_no: 'INV-81' } } : { data: held });
});
afterEach(cleanup);
async function addCart() {
  await screen.findByRole('button', { name: /Test shirt.*SHIRT-1/ }, { timeout: 10000 });
  await userEvent.selectOptions(screen.getByRole('combobox', { name: 'Open register shift' }), '5');
  await userEvent.click(screen.getByRole('button', { name: /Test shirt.*SHIRT-1/ }));
  await screen.findByRole('button', { name: 'Hold cart' }, { timeout: 10000 });
}

describe('D4 held-cart workflow', () => {
  it('holds through a popup with exact quantities and clears only after confirmation', async () => {
    open(); await addCart(); await userEvent.click(screen.getByRole('button', { name: 'Hold cart' }));
    const dialog = screen.getByRole('dialog', { name: 'Hold current cart' });
    await userEvent.type(within(dialog).getByLabelText('Cart label (optional)'), 'Fitting room');
    await userEvent.click(within(dialog).getByRole('button', { name: 'Save held cart' }));
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    expect(workspaceApi.save).toHaveBeenCalledWith('pos/held-carts', { idempotency_key: expect.any(String), label: 'Fitting room', cash_session_id: 5, customer_id: null, price_list_id: null, discount_amount: '0', items: [{ variant_id: 9, qty: '1' }] });
    expect(screen.queryByLabelText('Quantity for Test shirt · SHIRT-1')).not.toBeInTheDocument();
    expect(workspaceApi.save.mock.calls.some(([path]) => path === 'pos/checkout')).toBe(false);
  });

  it('recalls actual saved selections and requests a fresh server quote bound to the cart version', async () => {
    open('/pos?held=1&own=1&cash_session_id=5');
    const dialog = await screen.findByRole('dialog', { name: 'Your held carts' }, { timeout: 10000 });
    await userEvent.click(await within(dialog).findByRole('button', { name: 'Recall' }));
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    expect(screen.getByRole('combobox', { name: 'Customer (blank for walk-in)' })).toHaveValue('2');
    expect(screen.getByRole('combobox', { name: 'Price list (blank for catalog price)' })).toHaveValue('3');
    expect(screen.getByLabelText('Quantity for Test shirt · SHIRT-1')).toHaveValue('2.0000');
    expect(screen.queryByLabelText('Server quote')).not.toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', { name: 'Get server quote' }));
    await waitFor(() => expect(workspaceApi.save).toHaveBeenCalledWith('pos/quotes', expect.objectContaining({ held_cart_id: 8, held_cart_version: 4, cash_session_id: 5, customer_id: 2, price_list_id: 3, items: [{ variant_id: 9, qty: '2.0000' }] })));
    await userEvent.click(await screen.findByRole('button', { name: 'Complete sale' }));
    expect(await screen.findByText(/Sale completed: INV-81/)).toBeInTheDocument();
  });

  it('protects an existing unsold cart from replacement by Recall', async () => {
    open(); await addCart(); await userEvent.click(screen.getByRole('button', { name: 'Recall held cart' }));
    const dialog = screen.getByRole('dialog', { name: 'Your held carts' });
    expect(await within(dialog).findByRole('button', { name: 'Recall' })).toBeDisabled();
    expect(within(dialog).getByText(/Hold or clear your current cart/)).toBeInTheDocument();
    expect(workspaceApi.get).not.toHaveBeenCalledWith('pos/held-carts/8');
  });

  it('locks checkout during an unconfirmed hold and retries the identical key and body', async () => {
    let count = 0;
    workspaceApi.save.mockImplementation(async path => { if (path === 'pos/held-carts' && count++ === 0) throw { response: { status: 503, data: { message: 'Network result unconfirmed.' } } }; return { data: held }; });
    open(); await addCart(); await userEvent.click(screen.getByRole('button', { name: 'Hold cart' }));
    const dialog = screen.getByRole('dialog', { name: 'Hold current cart' }); await userEvent.click(within(dialog).getByRole('button', { name: 'Save held cart' }));
    const retryDialog = await screen.findByRole('dialog', { name: 'Retry unconfirmed hold' });
    expect(screen.getByLabelText('Open register shift')).toBeDisabled();
    expect(within(retryDialog).getByLabelText('Cart label (optional)')).toHaveAttribute('readonly');
    await userEvent.click(within(retryDialog).getByRole('button', { name: 'Retry same hold' }));
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
    const calls = workspaceApi.save.mock.calls.filter(([path]) => path === 'pos/held-carts'); expect(calls).toHaveLength(2); expect(calls[0][1]).toEqual(calls[1][1]);
  });

  it('validates quantity before storing and keeps the error at the top of the popup', async () => {
    open(); await addCart(); fireEvent.change(screen.getByLabelText('Quantity for Test shirt · SHIRT-1'), { target: { value: '0' } });
    await userEvent.click(screen.getByRole('button', { name: 'Hold cart' })); const dialog = screen.getByRole('dialog');
    await userEvent.click(within(dialog).getByRole('button', { name: 'Save held cart' })); expect(within(dialog).getByRole('alert')).toBeInTheDocument();
    expect(workspaceApi.save).not.toHaveBeenCalled();
  });

  it('requires confirmation to clear the editor and never discards a saved record implicitly', async () => {
    open(); await addCart(); await userEvent.click(screen.getByRole('button', { name: 'Clear current cart' }));
    const dialog = screen.getByRole('dialog', { name: 'Clear current cart?' });
    await userEvent.click(within(dialog).getByRole('button', { name: 'Clear current cart' }));
    expect(screen.queryByLabelText('Quantity for Test shirt · SHIRT-1')).not.toBeInTheDocument(); expect(workspaceApi.save).not.toHaveBeenCalled();
  });

  it('discards a selected own cart only after confirmation and sends its version', async () => {
    open('/', <HeldCartsDialog timezone="Asia/Karachi" canRecall onRecall={vi.fn()} onClose={vi.fn()}/>);
    await userEvent.click(await screen.findByRole('button', { name: 'Discard' }));
    expect(workspaceApi.save).not.toHaveBeenCalled();
    await userEvent.click(screen.getByRole('button', { name: 'Discard held cart' }));
    await waitFor(() => expect(workspaceApi.save).toHaveBeenCalledWith('pos/held-carts/8/discard', { version: 4, idempotency_key: expect.any(String) }));
  });
});
