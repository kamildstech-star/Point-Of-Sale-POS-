import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import { AuthProvider } from '../auth/AuthProvider';
import App from '../app/App';
import { authApi } from '../api/client';
import { workspaceApi } from '../api/workspace';

vi.mock('../api/client', async original => ({ ...(await original()), authApi: { me: vi.fn(), login: vi.fn(), logout: vi.fn(), tenants: vi.fn(), activate: vi.fn() } }));
vi.mock('../api/workspace', () => ({ workspaceApi: { get: vi.fn(), save: vi.fn() } }));
const owner = { id: 1, name: 'Owner', role: { name: 'Owner' }, permissions: ['products.view', 'settings.manage', 'inventory.view', 'inventory.adjust', 'inventory.approve', 'sales.view', 'sales.create', 'sales.discount', 'payments.record', 'customers.view', 'cash.open'], active_tenant: { id: 1, name: 'Test Shop', currency_code: 'PKR' } };
const page = data => ({ data, last_page: 1 });
const product = { id: 3, sku: 'SHIRT-3', name: 'Cotton shirt', default_price: '999.0000' };
const quoted = { id: 90, expires_at: '2099-10-19T12:00:00Z', quote: { currency_code: 'PKR', subtotal: '12.35', discount_total: '0.00', tax_total: '0.00', grand_total: '12.35' } };
function open(path = '/pos') { return render(<MemoryRouter initialEntries={[path]} future={{ v7_startTransition: true, v7_relativeSplatPath: true }}><AuthProvider><App/></AuthProvider></MemoryRouter>); }
beforeEach(() => {
  vi.resetAllMocks(); authApi.me.mockResolvedValue(owner);
  workspaceApi.get.mockImplementation(async path => {
    if (path === 'cash/sessions') return page([{ id: 5, register_id: 2 }]);
    if (path === 'operations/payment-methods') return page([{ id: 1, name: 'Cash', method_type: 'cash', active: true }, { id: 2, name: 'Card', method_type: 'card', active: true }]);
    if (path.startsWith('pos/products')) return page([product]);
    return page([]);
  });
});
async function prepareQuote() {
  const user = userEvent.setup(); await screen.findByRole('option', { name: 'Shift #5 · register 2' }); await user.selectOptions(screen.getByLabelText('Open register shift'), '5'); await user.click(await screen.findByRole('button', { name: /Cotton shirt/ }));
  workspaceApi.save.mockResolvedValue({ data: quoted }); await user.click(screen.getByRole('button', { name: 'Get server quote' })); await screen.findByLabelText('Server quote'); return user;
}

describe('inventory and POS workflows', () => {
  it('blocks invalid cart quantities and discounts before requesting a quote', async () => {
    open(); const user = userEvent.setup();
    await waitFor(() => expect(screen.getByLabelText('Open register shift').querySelector('option[value="5"]')).not.toBeNull(), { timeout: 10000 });
    await user.selectOptions(await screen.findByLabelText('Open register shift'), '5');
    await user.click(await screen.findByRole('button', { name: /Cotton shirt/ }));
    fireEvent.change(screen.getByLabelText('Quantity for Cotton shirt · SHIRT-3'), { target: { value: '0' } });
    fireEvent.change(screen.getByLabelText('Fixed order discount'), { target: { value: '-1' } });
    await user.click(screen.getByRole('button', { name: 'Get server quote' }));
    expect(workspaceApi.save).not.toHaveBeenCalled();
    expect(screen.getByLabelText('Quantity for Cotton shirt · SHIRT-3')).toHaveAttribute('aria-invalid', 'true');
    expect(screen.getByLabelText('Fixed order discount')).toHaveAttribute('aria-invalid', 'true');
  });
  it.each(['13.00', '12.3499', '0'])('blocks invalid or excessive walk-in payment %s before checkout', async amount => {
    open(); const user = await prepareQuote();
    fireEvent.change(screen.getByLabelText('Payment amount 1'), { target: { value: amount } });
    await user.click(screen.getByRole('button', { name: 'Complete sale' }));
    expect(workspaceApi.save.mock.calls.filter(([path]) => path === 'pos/checkout')).toHaveLength(0);
    expect(screen.getByRole('alert')).toHaveTextContent(/payment|precision/i);
    expect(screen.getByRole('button', { name: 'Complete sale' })).toBeEnabled();
  });
  it('uses scanner Enter to add an exact SKU and submits only quantities to the quote service', async () => {
    open(); const user = userEvent.setup(); await screen.findByRole('option', { name: 'Shift #5 · register 2' }); await user.selectOptions(screen.getByLabelText('Open register shift'), '5'); const scanner = await screen.findByLabelText('Scan barcode or search products'); await user.type(scanner, 'SHIRT-3{Enter}');
    expect(await screen.findByLabelText('Quantity for Cotton shirt · SHIRT-3')).toHaveValue('1'); expect(workspaceApi.get).toHaveBeenCalledWith('pos/products?search=SHIRT-3&scan=1');
    workspaceApi.save.mockResolvedValue({ data: quoted }); await user.click(screen.getByRole('button', { name: 'Get server quote' }));
    await waitFor(() => expect(workspaceApi.save).toHaveBeenCalledWith('pos/quotes', { cash_session_id: 5, customer_id: null, price_list_id: null, discount_amount: '0', items: [{ variant_id: 3, qty: '1' }] })); expect(await screen.findByLabelText('Server quote')).toHaveTextContent('PKR 12.35');
  });
  it('retries an unconfirmed checkout with exactly the same key and payment details', async () => {
    open(); const user = await prepareQuote(); workspaceApi.save.mockRejectedValueOnce(new Error('Network unavailable')).mockResolvedValueOnce({ data: { id: 42, invoice_no: 'INV-42' } });
    await user.click(screen.getByRole('button', { name: 'Complete sale' })); const first = workspaceApi.save.mock.calls.find(([path]) => path === 'pos/checkout')[1];
    expect(first.payments).toEqual([{ method_id: 1, amount: '12.35', reference: '' }]); expect(screen.getByLabelText('Payment amount 1')).toBeDisabled(); await user.click(await screen.findByRole('button', { name: 'Retry same checkout' }));
    const calls = workspaceApi.save.mock.calls.filter(([path]) => path === 'pos/checkout'); expect(calls).toHaveLength(2); expect(calls[1][1]).toEqual(first); expect(await screen.findByRole('link', { name: 'Open printable receipt' })).toHaveAttribute('href', '/sales/42');
  });
  it('blocks rapid duplicate clicks and invalidates quotes when quantities change', async () => {
    open(); const user = await prepareQuote(); await user.clear(screen.getByLabelText('Quantity for Cotton shirt · SHIRT-3')); expect(screen.queryByLabelText('Server quote')).not.toBeInTheDocument(); await user.type(screen.getByLabelText('Quantity for Cotton shirt · SHIRT-3'), '2');
    await user.click(screen.getByRole('button', { name: 'Get server quote' })); const button = await screen.findByRole('button', { name: 'Complete sale' }); workspaceApi.save.mockImplementation(() => new Promise(() => {})); fireEvent.click(button); fireEvent.click(button);
    expect(workspaceApi.save.mock.calls.filter(([path]) => path === 'pos/checkout')).toHaveLength(1);
  });
  it('sends split cash and confirmed external card amounts as decimal strings', async () => {
    open(); const user = await prepareQuote(); await user.clear(screen.getByLabelText('Payment amount 1')); await user.type(screen.getByLabelText('Payment amount 1'), '5.00'); await user.click(screen.getByRole('button', { name: 'Add split payment' })); await user.selectOptions(screen.getByLabelText('Payment method 2'), '2'); await user.type(screen.getByLabelText('Payment amount 2'), '7.35'); await user.type(screen.getByLabelText('Payment reference 2'), 'terminal-123'); workspaceApi.save.mockResolvedValue({ data: { id: 43, invoice_no: 'INV-43' } }); await user.click(screen.getByRole('button', { name: 'Complete sale' }));
    await waitFor(() => expect(workspaceApi.save).toHaveBeenCalledWith('pos/checkout', expect.objectContaining({ payments: [{ method_id: 1, amount: '5.00', reference: '' }, { method_id: 2, amount: '7.35', reference: 'terminal-123' }] })));
  });
  it('creates an adjustment for approval with decimal strings and displays validation errors', async () => {
    workspaceApi.get.mockImplementation(async path => path === 'catalog/locations' ? page([{ id: 1, name: 'Main store' }]) : path.startsWith('pos/products') ? page([product]) : page([]));
    workspaceApi.save.mockRejectedValue({ response: { status: 422, data: { message: 'Validation failed.', errors: { operation: ['Quantity does not match the unit precision.'] } } } });
    open('/inventory'); const user = userEvent.setup(); await user.click(await screen.findByRole('button', { name: 'New adjustment' })); await user.selectOptions(await screen.findByLabelText('Stock location'), '1'); await user.type(screen.getByLabelText('Reason'), 'Physical count correction'); await user.click(await screen.findByRole('button', { name: /Cotton shirt/ })); await user.click(screen.getByRole('button', { name: 'Save for approval' }));
    await waitFor(() => expect(workspaceApi.save).toHaveBeenCalledWith('inventory/adjustments', expect.objectContaining({ location_id: 1, items: [{ variant_id: 3, quantity_delta: '1' }] }))); expect(await screen.findByRole('alert')).toHaveTextContent('Quantity does not match the unit precision.');
  });
  it('requires a reviewed snapshot and location confirmation before activating a branch', async () => {
    workspaceApi.get.mockImplementation(async path => path.startsWith('catalog/branches') ? page([{ id: 1, name: 'Main', pos_engine: 'legacy', allow_credit: true }]) : { data: { preview_hash: 'a'.repeat(64), opening_rows: [], issues: [], legacy_freeze_scope: 'All legacy stock writers' } }); workspaceApi.save.mockResolvedValue({ data: { opening_rows: 0 } });
    open('/pos-settings'); const user = userEvent.setup(); await user.click(await screen.findByRole('button', { name: 'Open' })); const activate = await screen.findByRole('button', { name: 'Approve pricing and activate branch' }); await user.click(activate); expect(workspaceApi.save).not.toHaveBeenCalled(); await user.click(screen.getByRole('checkbox', { name: /I reviewed the physical/ })); await user.click(activate);
    await waitFor(() => expect(workspaceApi.save).toHaveBeenCalledWith('pos/branches/1/activate', expect.objectContaining({ preview_hash: 'a'.repeat(64), confirm_locations: true, allow_credit: true })));
  });
});
