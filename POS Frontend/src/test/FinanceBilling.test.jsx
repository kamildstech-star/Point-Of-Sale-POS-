import { beforeEach, describe, expect, it, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import BillingPage, { stripeRedirect } from '../features/operations/BillingPage';
import CashPage from '../features/operations/CashPage';
import ReturnsPage from '../features/operations/ReturnsPage';
import { workspaceApi } from '../api/workspace';

vi.mock('../api/workspace', () => ({ workspaceApi: { get: vi.fn(), save: vi.fn() } }));
vi.mock('../auth/AuthProvider', () => ({ useAuth: () => ({ user: { permissions: ['cash.close', 'sales.return', 'sales.refund'] } }) }));
const page = data => ({ data, last_page: 1 });
function open(element, path, route = '*') {
  return render(<MemoryRouter initialEntries={[path]} future={{ v7_startTransition: true, v7_relativeSplatPath: true }}><Routes><Route path={route} element={element}/>{route !== '*' && <Route path="*" element={<p>Return saved</p>}/>}</Routes></MemoryRouter>);
}
beforeEach(() => {
  vi.resetAllMocks();
  workspaceApi.get.mockImplementation(async path => {
    if (path === 'billing/plans') return { data: [{ id: 1, name: 'Starter', entitlements: [], prices: [{ id: 1, interval: 'monthly', amount: '19.00', currency_code: 'USD', checkout_enabled: false }, { id: 2, interval: 'yearly', amount: '190.00', currency_code: 'USD', checkout_enabled: false }] }], meta: { stripe_enabled: false } };
    if (path === 'billing/subscription') return { data: { subscription: { plan: { name: 'Preserved access' }, status: 'active', grandfathered: true }, access: { writable: true } } };
    return page([]);
  });
});
describe('billing and financial screens', () => {
  it('shows monthly/yearly prices and prevents unconfigured Checkout', async () => {
    open(<BillingPage/>, '/billing'); const user = userEvent.setup();
    expect(await screen.findByText('USD 19.00')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Continue to Stripe Checkout' })).toBeDisabled();
    await user.click(screen.getByRole('button', { name: 'Yearly' }));
    expect(screen.getByText('USD 190.00')).toBeInTheDocument();
    expect(workspaceApi.save).not.toHaveBeenCalled();
  });
  it('only reads subscription state after the success redirect and rejects foreign redirect hosts', async () => {
    open(<BillingPage/>, '/billing/success?session_id=untrusted');
    expect(await screen.findByText('Checkout returned')).toBeInTheDocument();
    await screen.findByText('Preserved access');
    expect(workspaceApi.save).not.toHaveBeenCalled();
    expect(() => stripeRedirect('https://checkout.stripe.com.evil.test/pay')).toThrow('Invalid billing destination.');
    expect(() => stripeRedirect('http://checkout.stripe.com/pay')).toThrow('Invalid billing destination.');
  });
  it('closes a shift with the counted decimal amount and an idempotency key', async () => {
    const report = { session: { id: 5, status: 'open' }, methods: [], expected_cash: '110.6900', cash_expenses: '4', cash_movements: '-10', cash_refunds: '0' };
    workspaceApi.get.mockImplementation(async path => path.endsWith('/reconciliation') ? { data: report } : path.startsWith('cash/sessions') ? page([{ id: 5, register_id: 1, status: 'open', opening_cash: '100.00' }]) : page([]));
    workspaceApi.save.mockResolvedValue({ data: {} }); open(<CashPage/>, '/cash'); const user = userEvent.setup();
    await user.click(await screen.findByRole('button', { name: 'Open' }));
    await user.click(await screen.findByRole('button', { name: 'Close shift' }));
    await user.type(await screen.findByLabelText('Actual counted cash'), '110.00');
    await user.click(screen.getByRole('button', { name: 'Close shift and store variance' }));
    await waitFor(() => expect(workspaceApi.save).toHaveBeenCalledWith('cash/sessions/5/close', { counted_cash: '110.00', idempotency_key: expect.any(String) }));
  });
  it('sends original sale-line references and the selected damaged disposition', async () => {
    workspaceApi.get.mockResolvedValue({ data: { invoice_no: 'INV-1', items: [{ id: 7, name_snapshot: 'Shirt', qty: '2', returned_qty: '0' }] } });
    workspaceApi.save.mockResolvedValue({ data: { id: 8 } });
    open(<ReturnsPage/>, '/returns/new/1', '/returns/new/:originalId'); const user = userEvent.setup();
    await user.type(await screen.findByLabelText('Reason for return'), 'Damaged seam');
    await user.clear(screen.getByLabelText('Quantity to return')); await user.type(screen.getByLabelText('Quantity to return'), '1');
    await user.selectOptions(screen.getByLabelText('Condition'), 'damaged');
    await user.click(screen.getByRole('button', { name: 'Request return' }));
    await waitFor(() => expect(workspaceApi.save).toHaveBeenCalledWith('sales/1/returns', { reason: 'Damaged seam', idempotency_key: expect.any(String), items: [{ sale_item_id: 7, qty: '1', disposition: 'damaged' }] }));
  });
});
