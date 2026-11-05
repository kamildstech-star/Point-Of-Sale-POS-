import { useRef } from 'react';
import { act, cleanup, fireEvent, render, renderHook, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { workspaceApi } from '../api/workspace';
import PosPage from '../features/operations/PosPage';
import { ProductFinder } from '../features/operations/OperationUI';
import { usePosSearch, useQuoteExpiry } from '../features/operations/posUsability';

vi.mock('../auth/AuthProvider', () => ({ useAuth: () => ({ user: { permissions: ['sales.create', 'sales.view', 'sales.discount'], active_tenant: { name: 'Test shop', currency_code: 'PKR', timezone: 'Asia/Karachi' } } }) }));
vi.mock('../api/workspace', () => ({ workspaceApi: { get: vi.fn(), save: vi.fn() } }));
const product = { id: 3, name: 'Test shirt', sku: 'SHIRT-3', default_price: '12.3456' };
const page = data => ({ data, last_page: 1 });
const quote = expires => ({ data: { id: 90, expires_at: new Date(expires ?? Date.now() + 600000).toISOString(), quote: { currency_code: 'PKR', grand_total: '12.35', subtotal: '12.35', tax_total: '0.00', discount_total: '0.00', allow_credit: true, currency_scale: 2 } } });
function open(path = '/pos?cash_session_id=5') { return render(<MemoryRouter initialEntries={[path]} future={{ v7_startTransition: true, v7_relativeSplatPath: true }}><PosPage /></MemoryRouter>); }
beforeEach(() => {
  vi.resetAllMocks();
  workspaceApi.get.mockImplementation(async path => path.startsWith('cash/sessions') ? page([{ id: 5, register_id: 2, register_name: 'Front counter', branch_name: 'Main Store' }]) : path === 'operations/payment-methods' ? page([{ id: 1, name: 'Cash', method_type: 'cash', active: true }]) : path.startsWith('pos/products') ? page([product]) : page([]));
  workspaceApi.save.mockResolvedValue(quote());
});
afterEach(() => { cleanup(); vi.useRealTimers(); vi.unstubAllGlobals(); });
function FocusHarness({ ready = true, locked = false, initialRecall = false, dialog = false }) {
  const searchRef = useRef(null);
  const focus = usePosSearch({ searchRef, ready, locked, initialRecall });
  return <><h1 tabIndex={-1}>Selling desk</h1><input aria-label="Scanner" ref={searchRef} disabled={locked} /><input aria-label="Payment" /><button onClick={focus}>Focus search</button>{dialog && <div role="dialog" aria-modal="true"><input aria-label="Dialog input" /></div>}</>;
}
describe('cashier search focus', () => {
  it('focuses an idle desktop entry once and preserves a payment field on later renders', async () => {
    const view = render(<FocusHarness />);
    await waitFor(() => expect(screen.getByLabelText('Scanner')).toHaveFocus());
    screen.getByLabelText('Payment').focus(); view.rerender(<FocusHarness />);
    expect(screen.getByLabelText('Payment')).toHaveFocus();
  });
  it('preserves a control the user focused before the options finished loading', async () => {
    const view = render(<FocusHarness ready={false} />); screen.getByLabelText('Payment').focus();
    view.rerender(<FocusHarness ready />);
    await new Promise(resolve => setTimeout(resolve, 40));
    expect(screen.getByLabelText('Payment')).toHaveFocus();
  });
  it.each([{ initialRecall: true }, { dialog: true }, { locked: true }])('does not autofocus over a recall, dialog or locked checkout: %j', async props => {
    render(<FocusHarness {...props} />); await new Promise(resolve => setTimeout(resolve, 40));
    expect(screen.getByLabelText('Scanner')).not.toHaveFocus();
  });
  it('does not automatically open the touch keyboard', async () => {
    vi.stubGlobal('matchMedia', vi.fn(() => ({ matches: true })));
    render(<FocusHarness />); await new Promise(resolve => setTimeout(resolve, 40));
    expect(screen.getByLabelText('Scanner')).not.toHaveFocus();
    await userEvent.click(screen.getByRole('button', { name: 'Focus search' }));
    expect(screen.getByLabelText('Scanner')).toHaveFocus();
    fireEvent.change(screen.getByLabelText('Scanner'), { target: { value: 'Old search' } });
    await userEvent.click(screen.getByRole('button', { name: 'Focus search' }));
    expect(screen.getByLabelText('Scanner').selectionStart).toBe(0);
    expect(screen.getByLabelText('Scanner').selectionEnd).toBe(10);
  });
  it('supports F2 outside editing, ignores modified keys, and remains inside dialogs', async () => {
    const view = render(<FocusHarness ready={false} />);
    const title = screen.getByRole('heading'); title.focus(); fireEvent.keyDown(title, { key: 'F2', ctrlKey: true }); expect(title).toHaveFocus();
    fireEvent.keyDown(title, { key: 'F2' }); expect(screen.getByLabelText('Scanner')).toHaveFocus();
    screen.getByLabelText('Payment').focus(); fireEvent.keyDown(screen.getByLabelText('Payment'), { key: 'F2' }); expect(screen.getByLabelText('Payment')).toHaveFocus();
    view.rerender(<FocusHarness ready={false} dialog />);
    screen.getByLabelText('Dialog input').focus(); fireEvent.keyDown(document, { key: 'F2' }); expect(screen.getByLabelText('Dialog input')).toHaveFocus();
  });
});
describe('quote expiry and safe transaction outcomes', () => {
  it('updates expiry without polling the server, including after returning to a tab', () => {
    vi.useFakeTimers(); const expiry = new Date(Date.now() + 1000).toISOString();
    const { result } = renderHook(() => useQuoteExpiry(expiry));
    expect(result.current).toBe(false);
    act(() => { vi.setSystemTime(Date.now() + 2000); window.dispatchEvent(new Event('focus')); });
    expect(result.current).toBe(true); expect(workspaceApi.get).not.toHaveBeenCalled();
  });
  it('blocks an expired new checkout and refreshes with unchanged cart inputs', async () => {
    workspaceApi.save.mockResolvedValueOnce(quote(Date.now() - 1000)).mockResolvedValueOnce(quote());
    open(); await userEvent.click(await screen.findByRole('button', { name: /Test shirt.*SHIRT-3/ }));
    await userEvent.click(screen.getByRole('button', { name: 'Get server quote' }));
    expect(await screen.findByRole('status')).toHaveTextContent('Quote expired');
    expect(screen.getByRole('button', { name: 'Complete sale' })).toBeDisabled();
    expect(workspaceApi.save.mock.calls.filter(([path]) => path === 'pos/checkout')).toHaveLength(0);
    await userEvent.click(screen.getByRole('button', { name: 'Refresh quote' }));
    await waitFor(() => expect(screen.getByRole('button', { name: 'Complete sale' })).toBeEnabled());
    expect(workspaceApi.save.mock.calls[1][1]).toEqual(workspaceApi.save.mock.calls[0][1]);
  });
  it('keeps the exact server amount to collect beside the action, including exchange credit', async () => {
    const result = quote(); result.data.quote.grand_total = '20.00'; result.data.quote.amount_to_collect = '12.35'; result.data.quote.exchange_credit_amount = '7.65';
    workspaceApi.save.mockResolvedValue(result);
    open('/pos?cash_session_id=5&exchange_return_id=7');
    await userEvent.click(await screen.findByRole('button', { name: /Test shirt.*SHIRT-3/ })); await userEvent.click(screen.getByRole('button', { name: 'Get server quote' }));
    const action = await screen.findByRole('button', { name: 'Complete sale' });
    expect(action.parentElement.querySelector('.pos-footer-total')).toHaveTextContent('Amount to collectPKR 12.35');
    expect(action.parentElement.querySelector('.pos-footer-total')).not.toHaveTextContent('20.00');
  });
  it('retries the identical checkout after its quote expires without creating a new quote', async () => {
    const expires = Date.now() + 60000;
    workspaceApi.save.mockResolvedValueOnce(quote(expires)).mockRejectedValueOnce({ response: { status: 503, data: { message: 'Outcome unknown.' } } }).mockResolvedValueOnce({ data: { id: 81, invoice_no: 'INV-81' } });
    open(); await userEvent.click(await screen.findByRole('button', { name: /Test shirt.*SHIRT-3/ })); await userEvent.click(screen.getByRole('button', { name: 'Get server quote' }));
    await userEvent.click(await screen.findByRole('button', { name: 'Complete sale' }));
    expect(await screen.findByRole('button', { name: 'Retry same checkout' })).toBeEnabled();
    const dateNow = vi.spyOn(Date, 'now').mockReturnValue(expires + 1000);
    fireEvent(window, new Event('focus'));
    await userEvent.click(screen.getByRole('button', { name: 'Retry same checkout' }));
    dateNow.mockRestore();
    const calls = workspaceApi.save.mock.calls;
    expect(calls.map(([path]) => path)).toEqual(['pos/quotes', 'pos/checkout', 'pos/checkout']);
    expect(calls[2][1]).toEqual(calls[1][1]);
    expect(await screen.findByRole('link', { name: 'Open printable receipt' })).toHaveAttribute('href', '/sales/81');
  });
  it('shows a known rejection and requires a fresh quote for a conflict', async () => {
    workspaceApi.save.mockResolvedValueOnce(quote()).mockRejectedValueOnce({ response: { status: 409, data: { message: 'Stock changed.' } } });
    open(); await userEvent.click(await screen.findByRole('button', { name: /Test shirt.*SHIRT-3/ })); await userEvent.click(screen.getByRole('button', { name: 'Get server quote' })); await userEvent.click(await screen.findByRole('button', { name: 'Complete sale' }));
    expect(await screen.findByText('Checkout needs attention')).toBeInTheDocument();
    expect(screen.queryByLabelText('Server quote')).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Retry same checkout' })).not.toBeInTheDocument();
    expect(screen.getByLabelText('Scan barcode or search products')).toBeEnabled();
  });
  it('starts a fresh editor only after the cashier chooses Start new sale', async () => {
    workspaceApi.save.mockResolvedValueOnce(quote()).mockResolvedValueOnce({ data: { id: 81, invoice_no: 'INV-81' } });
    open(); await screen.findByRole('button', { name: /Test shirt.*SHIRT-3/ });
    await userEvent.type(screen.getByLabelText('Scan barcode or search products'), 'Test');
    await waitFor(() => expect(screen.getByRole('button', { name: /Test shirt.*SHIRT-3/ })).toBeEnabled());
    await userEvent.click(screen.getByRole('button', { name: /Test shirt.*SHIRT-3/ })); await userEvent.click(screen.getByRole('button', { name: 'Get server quote' })); await userEvent.click(await screen.findByRole('button', { name: 'Complete sale' }));
    await userEvent.click(await screen.findByRole('button', { name: 'Start new sale' }));
    expect(screen.queryByRole('link', { name: 'Open printable receipt' })).not.toBeInTheDocument();
    expect(screen.getByLabelText('Customer (blank for walk-in)')).toHaveValue('');
    await waitFor(() => expect(screen.getByLabelText('Scan barcode or search products')).toHaveFocus());
    expect(screen.getByLabelText('Scan barcode or search products')).toHaveValue('');
    expect(workspaceApi.save).toHaveBeenCalledTimes(2);
  });
  it('leaves a completed exchange before starting another sale and retains the shift', async () => {
    workspaceApi.save.mockResolvedValueOnce(quote()).mockResolvedValueOnce({ data: { id: 81, invoice_no: 'EXCHANGE-81' } }).mockResolvedValueOnce(quote());
    open('/pos?cash_session_id=5&exchange_return_id=7');
    await userEvent.click(await screen.findByRole('button', { name: /Test shirt.*SHIRT-3/ })); await userEvent.click(screen.getByRole('button', { name: 'Get server quote' })); await userEvent.click(await screen.findByRole('button', { name: 'Complete sale' }));
    await userEvent.click(await screen.findByRole('button', { name: 'Start new sale' }));
    await waitFor(() => expect(screen.getByRole('button', { name: /Test shirt.*SHIRT-3/ })).toBeEnabled());
    await userEvent.click(screen.getByRole('button', { name: /Test shirt.*SHIRT-3/ })); await userEvent.click(screen.getByRole('button', { name: 'Get server quote' }));
    await waitFor(() => expect(workspaceApi.save).toHaveBeenCalledTimes(3));
    expect(workspaceApi.save.mock.calls[1][0]).toBe('returns/7/exchange');
    expect(workspaceApi.save.mock.calls[0][1].exchange_return_id).toBe(7);
    expect(workspaceApi.save.mock.calls[2][1]).not.toHaveProperty('exchange_return_id');
    expect(workspaceApi.save.mock.calls[2][1].cash_session_id).toBe(5);
  });
});
describe('scanner request safety', () => {
  it('suppresses rapid duplicate scans and ignores a response after the editor was locked', async () => {
    let finish;
    workspaceApi.get.mockImplementation(path => path.includes('&scan=1') ? new Promise(resolve => { finish = resolve; }) : Promise.resolve(page([product])));
    const add = vi.fn(), view = render(<ProductFinder onAdd={add} />);
    const input = screen.getByLabelText('Scan barcode or search products');
    fireEvent.change(input, { target: { value: 'SHIRT-3' } });
    fireEvent.keyDown(input, { key: 'Enter' }); fireEvent.keyDown(input, { key: 'Enter' });
    expect(workspaceApi.get.mock.calls.filter(([path]) => path.includes('&scan=1'))).toHaveLength(1);
    view.rerender(<ProductFinder onAdd={add} disabled />); view.rerender(<ProductFinder onAdd={add} disabled={false} />);
    await act(async () => { finish(page([product])); }); expect(add).not.toHaveBeenCalled();
  });
  it('explains an ambiguous scan without reporting it as a connection failure', async () => {
    workspaceApi.get.mockResolvedValue(page([])); render(<ProductFinder onAdd={vi.fn()} />);
    const input = screen.getByLabelText('Scan barcode or search products'); fireEvent.change(input, { target: { value: 'UNKNOWN' } }); fireEvent.keyDown(input, { key: 'Enter' });
    expect(await screen.findByRole('alert')).toHaveTextContent('Scan an exact SKU or unique barcode');
    expect(screen.queryByText('Unable to connect. Please try again.')).not.toBeInTheDocument();
  });
});
